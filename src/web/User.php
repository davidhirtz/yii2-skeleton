<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\web\MultiFieldSession;
use yii\web\Response;

/**
 * @property-read \davidhirtz\yii2\skeleton\models\User|null $identity {@see static::getIdentity()}
 * @method \davidhirtz\yii2\skeleton\models\User|null getIdentity($autoRenew = true)
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
     * @var bool whether 2FA via Google authenticator should be available.
     */
    public bool $enableGoogleAuthenticator = true;

    /**
     * @var string|null the IP address of the user
     */
    public ?string $ipAddress = null;

    /**
     * @var string the login type
     */
    public string $loginType = 'unknown';

    public $enableAutoLogin = true;
    public $identityClass = \davidhirtz\yii2\skeleton\models\User::class;
    public $loginUrl = null;

    public function init(): void
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

        $this->ipAddress ??= Yii::$app->getRequest()->getUserIP();

        parent::init();
    }

    public function loginRequired($checkAjax = true, $checkAcceptHeader = true): ?Response
    {
        // Set flash message for required logins.
        if (!$checkAjax || !Yii::$app->getRequest()->getIsAjax()) {
            Yii::$app->getSession()->addFlash('error', Yii::t('skeleton', 'You must login to view this page!'));
        }

        return parent::loginRequired($checkAjax, $checkAcceptHeader);
    }

    /**
     * @param \davidhirtz\yii2\skeleton\models\User $identity
     */
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        // Update login count, cache previous login date in session and insert new record to logins log.
        $session = Yii::$app->getSession();
        $session->set('last_login_timestamp', $identity->last_login?->getTimestamp());

        // Updates session's user id.
        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'ip_address' => ($ipAddress = Yii::$app->getRequest()->getUserIP()) ? inet_pton($ipAddress) : null,
                'user_id' => $identity->id,
            ];
        }

        // Update user record and insert login log.
        $identity->login_count++;
        $identity->last_login = new DateTime();

        if ($cookieBased) {
            $this->loginType = UserLogin::TYPE_COOKIE;
        }

        $this->insertLogin($identity);
        $identity->update(false);

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * Removes user id from session.
     * @param \davidhirtz\yii2\skeleton\models\User $identity
     */
    protected function afterLogout($identity): void
    {
        $session = Yii::$app->getSession();

        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'user_id' => null,
            ];
        }

        parent::afterLogout($identity);
    }

    private function insertLogin(\davidhirtz\yii2\skeleton\models\User $user): void
    {
        if ($browser = Yii::$app->getRequest()->getUserAgent()) {
            $browser = mb_substr((string) $browser, 0, 255, Yii::$app->charset);
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

        Yii::$app->getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
    }

    public function can($permissionName, $params = [], $allowCaching = true): bool
    {
        if ($this->disableRbacForGuests && $this->getIsGuest()) {
            return false;
        }

        return ($this->disableRbacForOwner && $this->identity?->isOwner())
            || parent::can($permissionName, $params, $allowCaching);
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
