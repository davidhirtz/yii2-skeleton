<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Models\Traits\SignupEmailTrait;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;

class SignupForm extends AbstractSignupForm
{
    use SignupEmailTrait;

    final public const string SESSION_TOKEN_NAME = 'signup_token';
    final public const string SESSION_TIMESTAMP_NAME = 'signup_timestamp';
    public const int SESSION_TOKEN_MIN_TIME = 5;
    public const int SESSION_TOKEN_MAX_TIME = 1800;

    public ?string $timezone = null;

    /**
     * A honeypot holding a random value JavaScript removes before the form is submitted.
     */
    public ?string $honeypot = null;

    public int $spamProtectionInSeconds = 60;

    public bool|string $terms = false;

    /**
     * Set by JavaScript and checked against the session.
     */
    public ?string $token = null;

    public function __construct($config = [])
    {
        $this->honeypot = Yii::$app->getSecurity()->generateRandomString(10);
        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                // Trimmed like every form a password is later typed into, the login first.
                ['email', 'name', 'password', 'token'],
                'trim',
            ],
            [
                ['email', 'name', 'password', 'terms'],
                'required',
            ],
            [
                ['password'],
                'string',
                'min' => $this->user->passwordMinLength,
                'max' => $this->user->passwordMaxLength,
            ],
            [
                ['terms'],
                'compare',
                'compareValue' => 1,
                'message' => Yii::t('skeleton', 'SIGNUP_TERMS_ERROR'),
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
                'message' => Yii::t('skeleton', 'SIGNUP_ERROR'),
            ],
            [
                ['timezone'],
                'string',
            ],
        ];
    }

    #[Override]
    protected function setUserAttributes(): void
    {
        parent::setUserAttributes();
        $this->user->timezone = $this->timezone;
    }

    #[Override]
    public function beforeValidate(): bool
    {
        if (!Application::current()->getUser()->isSignupEnabled()) {
            $this->addError('id', Yii::t('skeleton', 'SIGNUP_DISABLED_ERROR'));
            return false;
        }

        return parent::beforeValidate();
    }

    #[Override]
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

    protected function validateIp(): void
    {
        $webuser = WebUser::current();

        if (!$webuser || !$this->spamProtectionInSeconds) {
            return;
        }

        if ($webuser->ipAddress) {
            $signup = UserLogin::find()
                ->where(['type' => UserLogin::TYPE_SIGNUP, 'ip_address' => inet_pton($webuser->ipAddress)])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $duration = time() - $this->spamProtectionInSeconds;

            if ($signup?->created_at->getTimestamp() > $duration) {
                $this->addError('name', Yii::t('skeleton', 'SIGNUP_TOO_SOON_ERROR'));
            }
        }
    }

    /**
     * A token generated less than 2 seconds or more than 30 minutes ago is invalid.
     */
    protected function validateToken(): void
    {
        $token = $this->getSessionToken();

        if ($token !== null) {
            $tokenCreatedAt = Application::current()->getSession()->get(self::SESSION_TIMESTAMP_NAME);

            if ($this->token !== $token || $tokenCreatedAt === null) {
                $this->addError('token', Yii::t('skeleton', 'SIGNUP_ERROR'));
            }

            if (!$this->hasErrors('token')) {
                $timestamp = time() - $tokenCreatedAt;

                if ($timestamp < static::SESSION_TOKEN_MIN_TIME || $timestamp > static::SESSION_TOKEN_MAX_TIME) {
                    $this->addError('token', Yii::t('skeleton', 'SIGNUP_ERROR'));
                }
            }
        }
    }

    public function afterInsert(): void
    {
        if ($this->getSessionToken() !== null) {
            Application::current()->getSession()->set(static::SESSION_TOKEN_NAME, '');
        }

        $this->createUserLogin();

        if (!$this->sendSignupEmail()) {
            Application::current()->getSession()->addFlash('warning', Yii::t('skeleton', 'MAIL_SEND_ERROR', [
                'email' => $this->user->email,
            ]));
        }
    }

    private function createUserLogin(): void
    {
        $webuser = Application::current()->getUser();

        if ($webuser->isUnconfirmedEmailLoginEnabled() && !$webuser->isTwoFactorAuthenticationRequired($this->user)) {
            $webuser->loginType = UserLogin::TYPE_SIGNUP;
            $webuser->login($this->user);
            return;
        }

        $webuser->insertSignup($this->user);
    }

    /**
     * An override returning `null` disables the token check.
     */
    public function getSessionToken(): ?string
    {
        $session = Application::current()->getSession();
        $time = time();

        $isExpired = $session->get(self::SESSION_TIMESTAMP_NAME, 0) < $time - static::SESSION_TOKEN_MAX_TIME;

        if ($isExpired || !$session->get(self::SESSION_TOKEN_NAME)) {
            $session->set(self::SESSION_TOKEN_NAME, Yii::$app->getSecurity()->generateRandomString(20));
            $session->set(self::SESSION_TIMESTAMP_NAME, $time);
        }

        return $session->get(self::SESSION_TOKEN_NAME, false);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'terms' => Yii::t('skeleton', 'SIGNUP_TERMS_LABEL'),
        ];
    }
}
