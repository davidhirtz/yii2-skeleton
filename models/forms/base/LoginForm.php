<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use Yii;
use yii\base\Model;

/**
 * Class LoginForm
 * @package davidhirtz\yii2\skeleton\models\forms\base
 *
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class LoginForm extends Model
{
    use IdentityTrait;

    /**
     * @var bool
     */
    public $enableFacebookLogin = true;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string|null
     */
    public $code;

    /**
     * @var bool
     */
    public $rememberMe = true;

    /**
     * @var string
     */
    public $ipAddress;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['email', 'password'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['email', 'password'],
                'required',
            ],
            [
                ['email'],
                'email',
            ],
            [
                ['rememberMe'],
                'boolean',
            ],
            [
                ['code'],
                'safe',
            ],
        ];
    }

    /**
     * Validates user credentials and status and Google authenticator code if set.
     */
    public function afterValidate()
    {
        $this->validateUserPassword();
        $this->validateUserStatus();
        $this->validateLoginStatus();
        $this->validateGoogleAuthenticatorCode();

        parent::afterValidate();
    }

    /**
     * Validates password if user was found by email.
     */
    public function validateUserPassword()
    {
        if (!$this->hasErrors() && (!($user = $this->getUser()) || !$user->validatePassword($this->password))) {
            $this->addError('email', Yii::t('skeleton', 'Your email or password are incorrect.'));
        }
    }

    /**
     * Validates the user status if unconfirmed users are not allowed to log in via email.
     */
    public function validateLoginStatus()
    {
        if (($user = $this->getUser()) && $user->isUnconfirmed() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->addError('status', Yii::t('skeleton', 'Your email address is not confirmed yet. You should find a confirmation email in your inbox.'));
        }
    }

    /**
     * Validates the Google authenticator code if needed.
     */
    public function validateGoogleAuthenticatorCode()
    {
        if ($this->isGoogleAuthenticatorCodeRequired() && ($user = $this->getUser())) {
            $validator = new GoogleAuthenticatorValidator([
                'secret' => $user->google_2fa_secret,
                'datetime' => $user->last_login,
            ]);

            $validator->validateAttribute($this, 'code');
        }
    }

    /**
     * Logs in a user using the provided email and password.
     * @return bool
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->generatePasswordHash($this->password);
            $user->loginType = UserLogin::TYPE_LOGIN;
            $user->ipAddress = $this->ipAddress;

            return Yii::$app->getUser()->login($user, $this->rememberMe ? $user->cookieLifetime : 0);
        }

        return false;
    }

    /**
     * @param bool $clearErrors whether errors should be cleared if no code was set. This is useful if the login form and
     * Google authenticator code are filled out in two separate steps.
     * @return bool
     */
    public function isGoogleAuthenticatorCodeRequired($clearErrors = true): bool
    {
        if ($clearErrors && $this->hasErrors('code') && $this->code === null) {
            $this->clearErrors('code');
        }

        return ($user = $this->getUser()) && !empty($user->google_2fa_secret);
    }

    /**
     * @return bool
     */
    public function isFacebookLoginEnabled()
    {
        return $this->enableFacebookLogin && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Login';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('skeleton', 'Code'),
            'rememberMe' => Yii::t('skeleton', 'Keep me logged in'),
        ];
    }
}