<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\UserLogin;
use Override;
use Yii;
use yii\web\IdentityInterface;
use yii\web\MultiFieldSession;
use yii\web\Response;

/**
 * @extends \yii\web\User<\Hirtz\Skeleton\Models\User>
 */
class User extends \yii\web\User
{
    /**
     * @var int the cookie lifetime in seconds
     */
    public int $cookieLifetime = 2_592_000;

    /**
     * @var bool whether the role-based access management always returns `false` if user is not logged in
     */
    public bool $disableRbacForGuests = true;

    /**
     * @var bool whether the role-based access management always returns `true` if user is the site owner
     */
    public bool $disableRbacForOwner = true;

    /**
     * @var bool whether users can log in
     */
    public bool $enableLogin = true;

    /**
     * @var bool whether the user can log in without a confirmed email address
     */
    public bool $enableUnconfirmedEmailLogin = true;

    /**
     * @var bool whether users can reset their password
     */
    public bool $enablePasswordReset = true;

    /**
     * @var bool whether users can create new accounts
     */
    public bool $enableSignup = false;

    /**
     * @var bool whether 2FA via Authenticator should be available.
     */
    public bool $enableTwoFactorAuthentication = true;

    /**
     * @var bool whether the guest-facing forms must answer the same for an address that has an account and one
     * that does not. Turning it off restores the messages that name the reason — useful for a closed admin whose
     * support desk relies on them, at the cost of handing out a list of accounts.
     */
    public bool $enableUserEnumerationProtection = true;

    /**
     * @var string|null the IP address of the user
     */
    public ?string $ipAddress = null;

    /**
     * @var int how many failed logins an email address or an IP may accumulate before both are locked out, `0`
     * disables the lockout. A six-digit TOTP code with a discrepancy of one leaves three codes valid per period,
     * so the second factor needs this as much as the password does.
     */
    public int $loginAttemptLimit = 10;

    /**
     * @var int how long a lockout lasts, in seconds. Every further attempt restarts it.
     */
    public int $loginAttemptDuration = 900;

    /**
     * @var string the login type
     */
    public string $loginType = 'unknown';

    public $enableAutoLogin = true;
    public $identityClass = \Hirtz\Skeleton\Models\User::class;
    public $loginUrl = null;

    #[Override]
    public function init(): void
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

        $request = Yii::$app->getRequest();

        $this->ipAddress ??= $request->getUserIP();
        $this->identityCookie['secure'] ??= $request->getIsSecureConnection();

        parent::init();
    }

    #[Override]
    public function loginRequired($checkAjax = true, $checkAcceptHeader = true): ?Response
    {
        $request = Yii::$app->getRequest();

        // Set flash message for required logins.
        if (!$checkAjax || !$request->getIsAjax()) {
            Yii::$app->getSession()->addFlash('error', Yii::t('skeleton', 'USER_ERROR_MUST_LOGIN_VIEW'));
        }

        if ($request->isHtmxRequest()) {
            return Yii::$app->getResponse()->setHtmxRefresh();
        }

        return parent::loginRequired($checkAjax, $checkAcceptHeader);
    }

    /**
     * @param \Hirtz\Skeleton\Models\User $identity
     */
    #[Override]
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        $session = Yii::$app->getSession();
        $session->set('last_login_timestamp', $identity->last_login?->getTimestamp());

        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'ip_address' => ($ipAddress = Yii::$app->getRequest()->getUserIP()) ? inet_pton($ipAddress) : null,
                'user_id' => $identity->id,
            ];
        }

        $identity->login_count++;
        $identity->last_login = new DateTime();

        // Whoever just proved they know the password has no use for a reset link, and the one in their inbox
        // must not stay live behind them.
        $identity->clearPasswordResetToken();

        if ($cookieBased) {
            $this->loginType = UserLogin::TYPE_COOKIE;
        }

        $this->insertLogin($identity);
        $identity->update(false);

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * @param \Hirtz\Skeleton\Models\User $identity
     */
    #[Override]
    protected function afterLogout($identity): void
    {
        $session = Yii::$app->getSession();

        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'user_id' => null,
            ];
        }

        if (Yii::$app->getRequest()->isHtmxRequest()) {
            Yii::$app->getResponse()->setHtmxRefresh();
        }

        parent::afterLogout($identity);
    }

    private function insertLogin(\Hirtz\Skeleton\Models\User $user): void
    {
        $browser = Yii::$app->getRequest()->getUserAgent();

        if (is_string($browser)) {
            $browser = mb_substr($browser, 0, 255, Yii::$app->charset);
        }

        $ipAddress = $this->ipAddress ?: Yii::$app->getRequest()->getUserIP();
        $ipAddress = $ipAddress ? inet_pton($ipAddress) : null;

        $type = mb_substr($this->loginType, 0, 12, Yii::$app->charset);

        $columns = [
            'user_id' => $user->id,
            'type' => $type,
            'browser' => $browser,
            'ip_address' => $ipAddress,
            'created_at' => $user->last_login,
        ];

        UserLogin::getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
    }

    #[Override]
    public function can($permissionName, $params = [], $allowCaching = true): bool
    {
        if ($this->disableRbacForGuests && $this->getIsGuest()) {
            return false;
        }

        return ($this->disableRbacForOwner && $this->identity?->isOwner())
            || parent::can($permissionName, $params, $allowCaching);
    }

    public function isLoginAttemptLimitReached(?string $email = null): bool
    {
        if ($this->loginAttemptLimit < 1) {
            return false;
        }

        $cache = Yii::$app->getCache();

        foreach ($this->getLoginAttemptCacheKeys($email) as $key) {
            if ((int)$cache->get($key) >= $this->loginAttemptLimit) {
                return true;
            }
        }

        return false;
    }

    public function addFailedLoginAttempt(?string $email = null): void
    {
        if ($this->loginAttemptLimit < 1) {
            return;
        }

        $cache = Yii::$app->getCache();

        foreach ($this->getLoginAttemptCacheKeys($email) as $key) {
            $cache->set($key, (int)$cache->get($key) + 1, $this->loginAttemptDuration);
        }
    }

    public function resetFailedLoginAttempts(?string $email = null): void
    {
        $cache = Yii::$app->getCache();

        foreach ($this->getLoginAttemptCacheKeys($email) as $key) {
            $cache->delete($key);
        }
    }

    /**
     * Both the account and the origin are counted: the first stops a password being guessed, the second stops one
     * password being tried against every account.
     *
     * @return list<array{string, string, string, string}>
     */
    private function getLoginAttemptCacheKeys(?string $email): array
    {
        $keys = [];
        $email = mb_strtolower(trim((string)$email));

        if ($email !== '') {
            $keys[] = [self::class, 'login-attempts', 'email', $email];
        }

        if ($this->ipAddress) {
            $keys[] = [self::class, 'login-attempts', 'ip', $this->ipAddress];
        }

        return $keys;
    }

    /**
     * Rotating the auth key only invalidates the auto login cookies — every session row already written for this
     * user keeps working until it expires, so a password change has to delete them by hand.
     *
     * @return int the number of sessions destroyed
     */
    public function destroyOtherSessions(?\Hirtz\Skeleton\Models\User $user = null): int
    {
        $user ??= $this->getIdentity();
        $session = Yii::$app->getSession();

        if (!$user?->id || !$session instanceof DbSession) {
            return 0;
        }

        return $session->destroyUserSessions(
            $user->id,
            $session->getIsActive() ? $session->getId() : null
        );
    }

    /**
     * A flow that logs a user in without asking for a password — a password reset, an email confirmation, a signup —
     * must not skip the second factor. It has no code to check, so it has to refuse the login instead.
     */
    public function isTwoFactorAuthenticationRequired(\Hirtz\Skeleton\Models\User $user): bool
    {
        return $this->enableTwoFactorAuthentication && $user->hasTwoFactorAuthentication();
    }

    public function isLoginEnabled(): bool
    {
        return !!$this->enableLogin;
    }

    public function isUnconfirmedEmailLoginEnabled(): bool
    {
        return !!$this->enableUnconfirmedEmailLogin;
    }

    public function isPasswordResetEnabled(): bool
    {
        return !!$this->enablePasswordReset;
    }

    public function isSignupEnabled(): bool
    {
        return $this->enableSignup;
    }
}
