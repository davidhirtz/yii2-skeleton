<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\web\MultiFieldSession;
use yii\web\Response;

/**
 * @property Identity|null $identity
 * @method Identity|null getIdentity($autoRenew = true)
 */
class User extends \yii\web\User
{
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
     * @var bool whether login via cookie or long living access token is allowed
     */
    public $enableAutoLogin = true;

    /**
     * @var bool whether 2FA via Google authenticator should be available.
     */
    public bool $enableGoogleAuthenticator = true;

    /**
     * @var bool whether the role-based access management always returns `false` if user is not logged in
     */
    public bool $disableRbacForGuests = true;

    /**
     * @var bool whether the role-based access management always returns `true` if user is the site owner.
     * @see \davidhirtz\yii2\skeleton\models\User::isOwner()
     */
    public bool $disableRbacForOwner = true;

    public $identityClass = Identity::class;
    public $loginUrl = null;

    private ?int $_userCount = null;

    public function init(): void
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

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
     * @param Identity $identity
     */
    protected function afterLogin($identity, $cookieBased, $duration): void
    {
        // Update login count, cache previous login date in session and insert new record to logins log.
        $session = Yii::$app->getSession();
        $session->set('last_login_timestamp', $identity->last_login?->getTimestamp());

        // Updates session's user id.
        if ($session instanceof MultiFieldSession) {
            $session->writeCallback = fn () => [
                'ip_address' => inet_pton(Yii::$app->getRequest()->getUserIP()),
                'user_id' => $identity->id,
            ];
        }

        // Update user record and insert login log.
        $identity->login_count++;
        $identity->last_login = new DateTime();

        if ($cookieBased) {
            $identity->loginType = UserLogin::TYPE_COOKIE;
        }

        $this->insertLogin($identity);
        $identity->update(false);

        parent::afterLogin($identity, $cookieBased, $duration);
    }

    /**
     * Removes user id from session.
     * @param Identity $identity
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

    private function insertLogin(Identity $identity): void
    {
        if ($browser = Yii::$app->getRequest()->getUserAgent()) {
            $browser = mb_substr($browser, 0, 255, Yii::$app->charset);
        }

        if ($ipAddress = ($identity->ipAddress ?: Yii::$app->getRequest()->getUserIP())) {
            $ipAddress = inet_pton($ipAddress);
        }

        $columns = [
            'user_id' => $identity->id,
            'type' => $identity->loginType,
            'browser' => $browser,
            'ip_address' => $ipAddress,
            'created_at' => $identity->last_login,
        ];

        Yii::$app->getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
    }

    public function can($permissionName, $params = [], $allowCaching = true): bool
    {
        if ($this->disableRbacForGuests && $this->getIsGuest()) {
            return false;
        }

        return !(!$this->disableRbacForOwner || !$this->identity->isOwner()) || parent::can($permissionName, $params, $allowCaching);
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
        return $this->enableSignup || $this->getUserCount() == 0;
    }

    public function getUserCount(): int
    {
        $this->_userCount ??= (int)\davidhirtz\yii2\skeleton\models\User::find()->count();
        return $this->_userCount;
    }
}
