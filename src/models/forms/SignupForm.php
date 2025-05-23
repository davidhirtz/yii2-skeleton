<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;

class SignupForm extends AbstractSignupForm
{
    use SignupEmailTrait;

    final public const string SESSION_TOKEN_NAME = 'signup_token';
    final public const string SESSION_TIMESTAMP_NAME = 'signup_timestamp';
    public const SESSION_TOKEN_MIN_TIME = 5;
    public const SESSION_TOKEN_MAX_TIME = 1800;

    public ?string $timezone = null;

    /**
     * @var bool whether Facebook should be enabled
     */
    public bool $enableFacebookSignup = true;

    /**
     * @var string|null honeypot text field to mess with bots, the text field will have a random value which will be
     * removed by JavaScript before the form is submitted.
     */
    public ?string $honeypot = null;

    /**
     * @var int the time in seconds in which a new signup is not allowed from the same IP address.
     */
    public int $spamProtectionInSeconds = 60;

    /**
     * @var bool|string whether user has accepted the terms of service.
     */
    public bool|string $terms = false;

    /**
     * @var string|null token text field is set by ajax and checked against cookie.
     */
    public ?string $token = null;

    public function rules(): array
    {
        return [
            [
                ['email', 'name'],
                'trim',
            ],
            [
                ['email', 'name', 'password'],
                'required',
            ],
            [
                ['password'],
                'string',
                'min' => $this->user->passwordMinLength,
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
                'skipOnEmpty' => false,
            ],
            [
                ['honeypot'],
                'compare',
                'compareValue' => '',
                'message' => Yii::t('skeleton', 'Sign up could not be completed, please try again.'),
            ],
            [
                ['timezone'],
                'string',
            ],
        ];
    }

    protected function setUserAttributes(): void
    {
        parent::setUserAttributes();
        $this->user->timezone = $this->timezone;
    }

    public function beforeValidate(): bool
    {
        if (!Yii::$app->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'Sorry, signing up is currently disabled!'));
            return false;
        }

        // There were some cases in which the value set by the ajax call contained a leading space...
        $this->token = $this->token ? trim($this->token) : null;

        return parent::beforeValidate();
    }

    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->setUserAttributes();

            if (!$this->user->validate()) {
                $this->addErrors($this->user->getErrors());
            }

            $this->validateIp();
        }

        parent::afterValidate();
    }

    public function validateIp(): void
    {
        if (!Yii::$app->has('user') || !$this->spamProtectionInSeconds) {
            return;
        }

        $webuser = Yii::$app->getUser();

        if ($webuser->ipAddress) {
            $signup = UserLogin::find()
                ->where(['type' => UserLogin::TYPE_SIGNUP, 'ip_address' => inet_pton($webuser->ipAddress)])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $duration = time() - $this->spamProtectionInSeconds;

            if ($signup?->created_at->getTimestamp() > $duration) {
                $this->addError('id', Yii::t('skeleton', 'You have just created a new user account. Please wait a few minutes!'));
            }
        }
    }

    /**
     * Validates the token against the session token and the time the token was set. If the token was generated less
     * than 2 seconds ago or more than 30 minutes ago, the token is considered invalid.
     */
    public function validateToken(): void
    {
        $token = $this->getSessionToken();

        if ($token !== null) {
            $tokenCreatedAt = Yii::$app->getSession()->get(self::SESSION_TIMESTAMP_NAME);

            if ($this->token !== $token || $tokenCreatedAt === null) {
                $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
            }

            if (!$this->hasErrors('token')) {
                $timestamp = time() - $tokenCreatedAt;

                if ($timestamp < static::SESSION_TOKEN_MIN_TIME || $timestamp > static::SESSION_TOKEN_MAX_TIME) {
                    $this->addError('token', Yii::t('skeleton', 'Sign up could not be completed, please try again.'));
                }
            }
        }
    }

    public function afterInsert(): void
    {
        if ($this->getSessionToken() !== null) {
            Yii::$app->getSession()->set(static::SESSION_TOKEN_NAME, '');
        }

        $this->createUserLogin();
        $this->sendSignupEmail();
    }

    private function createUserLogin(): void
    {
        $webuser = Yii::$app->getUser();

        if ($webuser->isUnconfirmedEmailLoginEnabled()) {
            $webuser->loginType = UserLogin::TYPE_SIGNUP;
            $webuser->login($this->user);
        }
    }

    /**
     * Generates a random token saved in the user session. If the token is not set or expired, a new token is generated.
     *
     * Override this method to return null to disabled token check.
     */
    public function getSessionToken(): ?string
    {
        $session = Yii::$app->getSession();
        $time = time();

        $isExpired = $session->get(self::SESSION_TIMESTAMP_NAME, 0) < $time - static::SESSION_TOKEN_MAX_TIME;

        if ($isExpired || !$session->get(self::SESSION_TOKEN_NAME)) {
            $session->set(self::SESSION_TOKEN_NAME, Yii::$app->getSecurity()->generateRandomString(20));
            $session->set(self::SESSION_TIMESTAMP_NAME, $time);
        }

        return $session->get(self::SESSION_TOKEN_NAME, false);
    }

    public function isFacebookSignupEnabled(): bool
    {
        return $this->enableFacebookSignup && Yii::$app->getAuthClientCollection()->hasClient('facebook');
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'terms' => Yii::t('skeleton', 'I accept the terms of service and privacy policy'),
        ];
    }
}
