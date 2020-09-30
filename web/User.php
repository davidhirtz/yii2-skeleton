<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\datetime\DateTime;
use Yii;

/**
 * Class User
 * @package davidhirtz\yii2\skeleton\web
 *
 * @property Identity $identity
 * @method Identity getIdentity($autoRenew = true)
 */
class User extends \yii\web\User
{
    /**
     * @var bool whether users can log in
     */
    public $enableLogin = true;

    /**
     * @var bool whether the user can log in without a confirmed email address
     */
    public $enableUnconfirmedEmailLogin = true;

    /**
     * @var bool whether users can reset their password
     */
    public $enablePasswordReset = true;

    /**
     * @var bool whether users can create new accounts
     */
    public $enableSignup = false;

    /**
     * @var bool whether login via cookie or long living access token is allowed
     */
    public $enableAutoLogin = true;

    /**
     * @var bool whether the role based access management always returns `false` if user is not logged in
     */
    public $disableRbacForGuests = true;

    /**
     * @var bool whether the role based access management always returns `true` if user is the site owner.
     * @see \davidhirtz\yii2\skeleton\models\User::isOwner()
     */
    public $disableRbacForOwner = true;

    /**
     * @var string
     */
    public $identityClass = 'davidhirtz\yii2\skeleton\db\Identity';

    /**
     * @var array|null defaults to 403 error, if null admin module will set the correct
     * login url.
     */
    public $loginUrl = null;

    /**
     * @var int
     */
    private $_userCount;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->enableLogin) {
            $this->enableUnconfirmedEmailLogin = false;
            $this->enableAutoLogin = false;
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function loginRequired($checkAjax = true, $checkAcceptHeader = true)
    {
        // Set flash message for required logins.
        if (!$checkAjax || !Yii::$app->getRequest()->getIsAjax()) {
            Yii::$app->getSession()->addFlash('error', Yii::t('skeleton', 'You must login to view this page!'));
        }

        return parent::loginRequired($checkAjax, $checkAcceptHeader);
    }

    /**
     * @param Identity $identity
     * @param bool $cookieBased
     * @param int $duration
     */
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        /**
         * Update login count, cache previous login date in session and
         * insert new record to login log.
         */
        $session = Yii::$app->getSession();
        $session->set('last_login_timestamp', $identity->last_login ? $identity->last_login->getTimestamp() : null);

        /**
         * Updates session's user id.
         */
        $session->writeCallback = function () use ($identity) {
            return [
                'user_id' => $identity->id,
            ];
        };

        /**
         * Update user record and insert login log.
         */
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
     * @param Identity $identity
     */
    protected function afterLogout($identity)
    {
        /**
         * Removes user id from session.
         */
        Yii::$app->getSession()->writeCallback = function () {
            return [
                'user_id' => null,
            ];
        };

        parent::afterLogout($identity);
    }

    /**
     * @param Identity $identity
     */
    private function insertLogin($identity)
    {
        $columns = [
            'user_id' => $identity->id,
            'type' => $identity->loginType,
            'browser' => mb_substr(Yii::$app->getRequest()->getUserAgent(), 0, 255),
            'ip_address' => inet_pton($identity->ipAddress ?: Yii::$app->getRequest()->getUserIP()),
            'created_at' => $identity->last_login,
        ];

        Yii::$app->getDb()->createCommand()->insert(UserLogin::tableName(), $columns)->execute();
    }

    /**
     * @inheritDoc
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        if ($this->disableRbacForGuests && $this->getIsGuest()) {
            return false;
        }

        return (!$this->disableRbacForOwner && !$this->identity->isOwner()) ? parent::can($permissionName, $params, $allowCaching) : true;
    }

    /**
     * @return bool
     */
    public function isLoginEnabled()
    {
        return (bool)$this->enableLogin;
    }

    /**
     * @return bool
     */
    public function isUnconfirmedEmailLoginEnabled()
    {
        return (bool)$this->enableUnconfirmedEmailLogin;
    }

    /**
     * @return bool
     */
    public function isPasswordResetEnabled()
    {
        return (bool)$this->enablePasswordReset;
    }

    /**
     * @return bool
     */
    public function isSignupEnabled()
    {
        return $this->enableSignup || $this->getUserCount() == 0;
    }

    /**
     * @return int|string
     */
    public function getUserCount()
    {
        if ($this->_userCount === null) {
            $this->_userCount = \davidhirtz\yii2\skeleton\models\User::find()->count();
        }

        return $this->_userCount;
    }
}