<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\SecretKey;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;

class LoginHandoff
{
    public int $tokenLifetime = 60;

    /**
     * Covers a login on the auth host, two-factor code included.
     */
    public int $stateLifetime = 900;

    public int $logoutLifetime = 60;

    /**
     * Ties the token to the browser that asked for it: a callback link sent to someone else finds no cookie (login
     * CSRF).
     *
     * @var array<string, mixed>
     */
    public array $stateCookie = ['name' => '_handoff', 'httpOnly' => true, 'sameSite' => Cookie::SAME_SITE_LAX];

    /**
     * Holds the auth host's tag in a session it handed out, for the logout.
     */
    public string $tagSessionParam = '__handoffTag';

    public static function create(): static
    {
        return Yii::$container->get(static::class);
    }

    /**
     * A keyed hash, so a session row never holds another session's id.
     */
    public static function getTag(string $sessionId): string
    {
        return hash_hmac('sha256', "handoff|$sessionId", SecretKey::get());
    }

    /**
     * Sets the state cookie on this host, the only one able to finish the hand-off.
     */
    public function getStartUrl(string $return): string
    {
        $state = Yii::$app->getSecurity()->generateRandomString();
        $request = Application::current()->getRequest();

        Application::current()->getResponse()->getCookies()->add(Yii::$container->get(Cookie::class, [], [
            ...$this->stateCookie,
            'value' => $state,
            'expire' => time() + $this->stateLifetime,
            'secure' => $request->getIsSecureConnection(),
        ]));

        return $this->getAdminUrl($this->getWebUser()->getAuthHostInfo() ?? '', 'sso', [
            'return' => $return,
            'state' => $state,
        ]);
    }

    public function issue(User $user, string $return, string $state): string
    {
        $session = Application::current()->getSession();
        $hostInfo = $this->getWebUser()->getHandoffHostInfo($return);

        if (
            !$session instanceof DbSession
            || !$session->getIsActive()
            || $hostInfo === null
            || $hostInfo === Application::current()->getRequest()->getHostInfo()
            || !preg_match('/^[\w-]{32}$/', $state)
        ) {
            throw new BadRequestHttpException(Yii::t('skeleton', 'LOGIN_HANDOFF_ERROR'));
        }

        $token = Yii::$app->getSecurity()->generateRandomString(48);
        $tag = static::getTag($session->getId());

        UserToken::issue($user->id, UserToken::TYPE_LOGIN_HANDOFF, $this->getTokenValue($token, $state, $tag, $return), $this->tokenLifetime);

        return $this->getAdminUrl($hostInfo, 'sso/callback', [
            'token' => $token,
            'tag' => $tag,
            'return' => $return,
        ]);
    }

    /**
     * Single use: only the request deleting the row logs in.
     */
    public function consume(string $token, string $tag, string $return): User
    {
        $request = Application::current()->getRequest();
        $state = $request->getCookies()->getValue($this->stateCookie['name']);

        Application::current()->getResponse()->getCookies()->remove(Yii::$container->get(Cookie::class, [], [
            ...$this->stateCookie,
            'value' => '',
        ]));

        $record = is_string($state)
        && str_starts_with($return, $request->getHostInfo() . '/')
        && $this->getWebUser()->isHandoffUrl($return)
            ? UserToken::find()
                ->whereType(UserToken::TYPE_LOGIN_HANDOFF)
                ->whereToken($this->getTokenValue($token, $state, $tag, $return))
                ->unexpired()
                ->with('user')
                ->one()
            : null;

        $user = $record && UserToken::deleteAll(['id' => $record->id]) === 1 ? $record->user : null;
        $webuser = $this->getWebUser();

        $isValid = $user
            && $webuser->isLoginEnabled()
            && !$user->isDisabled()
            && (!$user->isUnconfirmed() || $webuser->isUnconfirmedEmailLoginEnabled());

        if (!$isValid) {
            throw new BadRequestHttpException(Yii::t('skeleton', 'LOGIN_HANDOFF_ERROR'));
        }

        return $user;
    }

    /**
     * Signed, so no other site can end the auth host's session with a link.
     */
    public function getLogoutUrl(string $tag, string $return): string
    {
        $expires = time() + $this->logoutLifetime;

        return $this->getAdminUrl($this->getWebUser()->getAuthHostInfo() ?? '', 'sso/logout', [
            'tag' => $tag,
            'expires' => $expires,
            'return' => $return,
            'signature' => $this->getLogoutSignature($tag, $expires, $return),
        ]);
    }

    public function validateLogout(string $tag, int $expires, string $return, string $signature): bool
    {
        return $expires >= time()
            && hash_equals($this->getLogoutSignature($tag, $expires, $return), $signature)
            && $this->getWebUser()->isHandoffUrl($return);
    }

    /**
     * The auth host and the hand-off host may be different tenants with path prefixes of their own, but either answers
     * the admin at its host's root.
     *
     * @param array<string, mixed> $params
     */
    protected function getAdminUrl(string $hostInfo, string $route, array $params): string
    {
        return $hostInfo . '/' . Yii::$app->getAdminAlias() . "/$route?" . http_build_query($params);
    }

    protected function getTokenValue(string $token, string $state, string $tag, string $return): string
    {
        return "$token|$state|$tag|$return";
    }

    protected function getLogoutSignature(string $tag, int $expires, string $return): string
    {
        return hash_hmac('sha256', "handoff-logout|$tag|$expires|$return", SecretKey::get());
    }

    protected function getWebUser(): \Hirtz\Skeleton\Web\User
    {
        return Application::current()->getUser();
    }
}
