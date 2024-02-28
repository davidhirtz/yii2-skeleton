<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\SignupEmailTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\base\Model;

class SignupForm extends Model
{
    use ModelTrait;
    use SignupEmailTrait;

    final public const SESSION_TOKEN_NAME = 'signup_token';
    final public const SESSION_TIMESTAMP_NAME = 'signup_timestamp';
    public const SESSION_MIN_TIME = 2;
    public const SESSION_MAX_TIME = 1800;

    public ?string $email = null;
    public ?string $name = null;
    public ?string $password = null;
    public ?string $timezone = null;

    /**
     * @var bool whether Facebook should be enabled
     */
    public bool $enableFacebookSignup = true;

    /**
     * @var string|null honeypot text field to mess with bots, the text field will have a random value which will be
     * removed by javascript before the form is submitted.
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

    public ?User $user = null;

    public function init(): void
    {
        $this->user ??= User::create();
        $this->user->setScenario($this->user::SCENARIO_INSERT);

        parent::init();
    }

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
        $this->user->status ??= User::STATUS_ENABLED;
        $this->user->email = $this->email;
        $this->user->name = $this->name;
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

    public function insert(): bool
    {
        if (!$this->validate() || !$this->beforeInsert()) {
            return false;
        }

        if ($this->user->insert(false)) {
            $this->afterInsert();
            return true;
        }

        return false;
    }

    public function beforeInsert(): bool
    {
        $this->user->generatePasswordHash($this->password);
        $this->user->generateVerificationToken();

        return true;
    }

    public function afterInsert(): void
    {
        if (static::getSessionToken() !== null) {
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
