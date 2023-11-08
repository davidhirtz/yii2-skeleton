<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use Yii;
use yii\base\Model;

/**
 * @property Identity $user
 */
class LoginForm extends Model
{
    use IdentityTrait;

    public bool $enableFacebookLogin = true;
    public ?string $password = null;
    public ?string $code = null;
    public bool|string $rememberMe = true;
    public ?string $ipAddress = null;

    private bool $_isGoogleAuthenticatorCodeRequired = false;

    public function rules(): array
    {
        return [
            [
                ['email', 'password'],
                'trim',
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
                ['code'],
                'string',
            ],
            [
                ['rememberMe'],
                'boolean',
            ],
        ];
    }

    /**
     * Validates user credentials and status and Google authenticator code if set.
     */
    public function afterValidate(): void
    {
        $this->validateUserPassword();
        $this->validateUserStatus();
        $this->validateLoginStatus();
        $this->validateGoogleAuthenticatorCode();

        parent::afterValidate();
    }

    /**
     * Validates password if email found user. If any other error occurred during validation, don't even bother.
     */
    public function validateUserPassword(): void
    {
        if (!$this->hasErrors() && !(($user = $this->getUser()) && $user->validatePassword($this->password))) {
            $this->addError('email', Yii::t('skeleton', 'Your email or password are incorrect.'));
        }
    }

    /**
     * Validates the user status if unconfirmed users are not allowed to log in via email.
     */
    public function validateLoginStatus(): void
    {
        if (($user = $this->getUser()) && $user->isUnconfirmed() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->addError('status', Yii::t('skeleton', 'Your email address is not confirmed yet. You should find a confirmation email in your inbox.'));
        }
    }

    /**
     * Validates the Google authenticator code if needed.
     */
    public function validateGoogleAuthenticatorCode(): void
    {
        if (Yii::$app->getUser()->enableGoogleAuthenticator && !$this->hasErrors() && ($user = $this->getUser()) && $user->google_2fa_secret) {
            /** @var GoogleAuthenticatorValidator $validator */
            $validator = Yii::createObject([
                'class' => GoogleAuthenticatorValidator::class,
                'secret' => $user->google_2fa_secret,
                'datetime' => $user->last_login,
            ]);

            $validator->validateAttribute($this, 'code');
            $this->_isGoogleAuthenticatorCodeRequired = true;
        }
    }

    /**
     * Logs in a user using the provided email and password.
     */
    public function login(): bool
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->generatePasswordHash($this->password);
            $user->loginType = UserLogin::TYPE_LOGIN;
            $user->ipAddress = $this->ipAddress;

            return Yii::$app->getUser()->login($user, $this->rememberMe ? $user->cookieLifetime : 0);
        }

        // Don't show empty error if the user has not been able to enter it...
        if ($this->hasErrors('code') && $this->code === null) {
            $this->clearErrors('code');
        }

        return false;
    }

    public function isGoogleAuthenticatorCodeRequired(): bool
    {
        return $this->_isGoogleAuthenticatorCodeRequired;
    }

    public function isFacebookLoginEnabled(): bool
    {
        return $this->enableFacebookLogin && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    public function formName(): string
    {
        return 'Login';
    }

    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'Email'),
            'password' => Yii::t('skeleton', 'Password'),
            'code' => Yii::t('skeleton', 'Code'),
            'rememberMe' => Yii::t('skeleton', 'Keep me logged in'),
        ];
    }
}
