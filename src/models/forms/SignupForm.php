<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;

class SignupForm extends Identity
{
    use SignupEmailTrait;

    /**
     * @var bool whether Facebook should be enabled
     */
    public bool $enableFacebookSignup = true;

    /**
     * @var string|null the password
     */
    public ?string $password = null;

    /**
     * @var string|null honeypot text field to mess with bots, the text field will have a random value which will be
     * removed by javascript before the form is submitted.
     */
    public ?string $honeypot = null;

    /**
     * @var bool|string whether user has accepted the terms of service.
     */
    public bool|string $terms = false;

    /**
     * @var string|null token text field is set by ajax and checked against cookie.
     */
    public ?string $token = null;

    /**
     * @var int the time in seconds in which a new signup is not allowed from the same IP address.
     */
    public int $spamProtectionInSeconds = 60;

    public const SESSION_TOKEN_NAME = 'signup_token';
    public const SESSION_TIMESTAMP_NAME = 'signup_timestamp';
    public const SESSION_MIN_TIME = 2;
    public const SESSION_MAX_TIME = 1800;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['password'],
                'required',
            ],
            [
                ['password'],
                'string',
                'min' => $this->passwordMinLength,
            ],
            [
                ['terms'],
                'compare',
                'compareValue' => 1,
                'message' => Yii::t('skeleton', 'Please accept the terms of service and privacy policy.'),
                'skipOnEmpty' => false,
            ],
            [
                ['token'],
                $this->validateToken(...),
            ],
            [
                ['honeypot'],
                'compare',
                'compareValue' => '',
                'message' => Yii::t('skeleton', 'Sign up could not be completed, please try again.'),
            ],
        ];
    }

    public function validateToken(): void
    {
        $token = static::getSessionToken();

        if ($token !== null) {
            if ($this->token !== $token) {
                $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
            }

            if (!$this->hasErrors('token')) {
                $timestamp = time() - Yii::$app->getSession()->get(static::SESSION_TIMESTAMP_NAME, 0);
                if ($timestamp < static::SESSION_MIN_TIME && $timestamp > static::SESSION_MAX_TIME) {
                    $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
                }
            }
        }
    }

    public function beforeValidate(): bool
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
            return false;
        }

        $this->status ??= static::STATUS_ENABLED;
        $this->ipAddress ??= Yii::$app->getRequest()->getUserIP();

        // There were some cases in which the value set by the ajax call contained a leading space…
        $this->token = $this->token ? trim($this->token) : null;

        return parent::beforeValidate();
    }

    public function validateIp(): void
    {
        if ($this->ipAddress && $this->spamProtectionInSeconds > 0) {
            $signup = UserLogin::find()
                ->where(['type' => UserLogin::TYPE_SIGNUP, 'ip_address' => inet_pton($this->ipAddress)])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            if ($signup && $signup->created_at->getTimestamp() > time() - $this->spamProtectionInSeconds) {
                $this->addError('id', Yii::t('skeleton', 'You have just created a new user account. Please wait a few minutes!'));
            }
        }
    }

    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->validateIp();
        }

        parent::afterValidate();
    }

    public function beforeSave($insert): bool
    {
        if ($insert) {
            $this->generatePasswordHash($this->password);
            $this->generateVerificationToken();
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert) {
            if (static::getSessionToken() !== null) {
                Yii::$app->getSession()->set(static::SESSION_TOKEN_NAME, '');
            }

            $this->createUserLogin();
            $this->sendSignupEmail();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    private function createUserLogin(): void
    {
        if (Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->loginType = UserLogin::TYPE_SIGNUP;
            Yii::$app->getUser()->login($this);
        }
    }

    /**
     * Generates a random token saved in the user session. Override this method to return null to disabled token check.
     */
    public static function getSessionToken(): ?string
    {
        $time = time();
        $session = Yii::$app->getSession();

        if ($session->get(static::SESSION_TIMESTAMP_NAME, 0) < $time - 300 || !$session->get(static::SESSION_TOKEN_NAME)) {
            $session->set(static::SESSION_TOKEN_NAME, Yii::$app->getSecurity()->generateRandomString(20));
            $session->set(static::SESSION_TIMESTAMP_NAME, $time);
        }

        return $session->get(static::SESSION_TOKEN_NAME, false);
    }

    public function isFacebookSignupEnabled(): bool
    {
        return $this->enableFacebookSignup && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'password' => Yii::t('skeleton', 'Password'),
            'terms' => Yii::t('skeleton', 'I accept the terms of service and privacy policy'),
        ];
    }
}
