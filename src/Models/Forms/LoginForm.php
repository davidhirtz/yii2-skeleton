<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    use IdentityTrait;
    use ModelTrait;

    /**
     * A bcrypt hash at the default cost that nothing matches, so a login for an unknown address costs the same as
     * one for a known address with the wrong password.
     */
    private const string DUMMY_PASSWORD_HASH = '$2y$13$DbNN/izySYY01sK3RTJb2OZa1Kc1Kr/PImftEuYLT.2IbCbvukEWu';

    /**
     * The session key of a login whose password was right and whose two-factor code is still to come.
     */
    final public const string PENDING_SESSION_KEY = 'pendingLogin';

    public ?string $password = null;
    public ?string $code = null;
    public bool|string $rememberMe = true;

    /**
     * @var int the seconds the second step may follow the first
     */
    public int $pendingDuration = 300;

    private bool $is2FaRequired = false;
    private bool $isPending = false;

    #[Override]
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
                'when' => fn () => !$this->isPending,
            ],
            [
                ['email'],
                'email',
                'when' => fn () => !$this->isPending,
            ],
            [
                ['email'],
                $this->validateEmail(...),
                'when' => fn () => !$this->isPending && !$this->hasErrors(),
            ],
            [
                ['password'],
                $this->validatePassword(...),
                'when' => fn () => !$this->isPending && !$this->hasErrors('password'),
            ],
            [
                ['code'],
                'string',
                'min' => 6,
                'max' => User::RECOVERY_CODE_LENGTH,
            ],
            [
                ['rememberMe'],
                'boolean',
            ],
        ];
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $webuser = Application::current()->getUser();

        if (!$webuser->isLoginEnabled()) {
            $this->addError('email', Yii::t('skeleton', 'USER_SORRY_LOGGING_CURRENTLY'));
            return false;
        }

        if ($webuser->isLoginAttemptLimitReached($this->email)) {
            $this->addError('email', Yii::t('skeleton', 'LOGIN_TOO_MANY_ATTEMPTS'));
            return false;
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors() && $this->user) {
            $this->validateUserStatus();
            $this->validateLoginStatus();
            $this->validateTwoFactorAuthenticatorCode();
        }

        parent::afterValidate();
    }

    protected function validatePassword(): void
    {
        if (!$this->user) {
            // No account matched, and `validateEmail()` has already said so. Hash anyway: skipping it would make
            // an address that exists measurably slower to reject than one that does not.
            Yii::$app->getSecurity()->validatePassword((string)$this->password, self::DUMMY_PASSWORD_HASH);
            return;
        }

        if (!$this->user->validatePassword((string)$this->password)) {
            $this->addError('email', Yii::t('skeleton', 'USER_EMAIL_PASSWORD_INCORRECT'));
        }
    }

    protected function validateLoginStatus(): void
    {
        if ($this->user->isUnconfirmed() && !Application::current()->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->addError('status', Yii::t('skeleton', 'USER_EMAIL_ADDRESS_NOT'));
        }
    }

    protected function validateTwoFactorAuthenticatorCode(): void
    {
        if (!Application::current()->getUser()->isTwoFactorAuthenticationRequired($this->user)) {
            return;
        }

        $this->is2FaRequired = true;

        // A recovery code is longer than a TOTP one, which is how the two are told apart
        if (strlen((string)$this->code) === User::RECOVERY_CODE_LENGTH) {
            if (!$this->user->validateTwoFactorAuthenticationRecoveryCode($this->code)) {
                $this->addInvalidCodeError();
            }

            return;
        }

        $validator = Yii::$container->get(TwoFactorAuthenticationValidator::class, [], [
            'secret' => $this->user->getTwoFactorAuthenticationSecret(),
            'datetime' => $this->user->last_login,
        ]);

        $validator->validateAttribute($this, 'code');
    }

    private function addInvalidCodeError(): void
    {
        $this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel('code'),
        ]));
    }

    public function login(): bool
    {
        $webuser = Application::current()->getUser();

        // The second step posts the code alone: the password stays out of the page it would have to be written into.
        if ($this->password === null && $this->code !== null && !$this->resumePendingLogin()) {
            $this->addError('email', Yii::t('skeleton', 'LOGIN_TWO_FACTOR_EXPIRED'));
            return false;
        }

        if ($this->validate()) {
            $this->removePendingLogin();

            $webuser->loginType = UserLogin::TYPE_LOGIN;
            $webuser->resetFailedLoginAttempts($this->email);

            if (!$this->isPending && $this->user->isPasswordHashOutdated()) {
                $this->user->generatePasswordHash((string)$this->password);
            }

            return $webuser->login($this->user, $this->rememberMe ? $webuser->cookieLifetime : 0);
        }

        $isAwaitingCode = $this->isOnlyTheCodeMissing() && (string)$this->code === '';

        if ($this->isOnlyTheCodeMissing()) {
            $this->setPendingLogin();
        }

        // A blank form is a mistake, not an attempt — only a submission that got as far as a credential counts. A
        // correct password still awaiting its code is the first step of a login, not a failure.
        if ($this->email && ($this->password || $this->isPending) && !$isAwaitingCode) {
            $webuser->addFailedLoginAttempt($this->email);
        }

        if (null === $this->code) {
            $this->clearErrors('code');
        }

        return false;
    }

    private function isOnlyTheCodeMissing(): bool
    {
        return $this->is2FaRequired
            && $this->user !== null
            && array_keys($this->getErrors()) === ['code'];
    }

    /**
     * The password is verified at this point and is not seen again, so an outdated hash is renewed now, whatever
     * the code turns out to be.
     */
    private function setPendingLogin(): void
    {
        if (!$this->isPending) {
            if ($this->user->isPasswordHashOutdated()) {
                $this->user->generatePasswordHash((string)$this->password);
                $this->user->updateAttributes(['password_hash', 'password_scheme']);
            }

            Application::current()->getSession()->set(self::PENDING_SESSION_KEY, [
                'id' => $this->user->id,
                'authKey' => $this->user->auth_key,
                'rememberMe' => (bool)$this->rememberMe,
                'expires' => time() + $this->pendingDuration,
            ]);
        }
    }

    /**
     * A pending login ends with its time, and with the account's auth key, which a password change rotates.
     */
    private function resumePendingLogin(): bool
    {
        $pending = Application::current()->getSession()->get(self::PENDING_SESSION_KEY);

        if (!is_array($pending) || (int)($pending['expires'] ?? 0) < time()) {
            $this->removePendingLogin();
            return false;
        }

        $user = User::findOne(['id' => (int)($pending['id'] ?? 0)]);

        if (!$user || !hash_equals((string)$user->auth_key, (string)($pending['authKey'] ?? ''))) {
            $this->removePendingLogin();
            return false;
        }

        $this->user = $user;
        $this->email = $user->email;
        $this->rememberMe = (bool)($pending['rememberMe'] ?? false);
        $this->isPending = true;

        return true;
    }

    private function removePendingLogin(): void
    {
        Application::current()->getSession()->remove(self::PENDING_SESSION_KEY);
    }

    public function isTwoFactorAuthenticationCodeRequired(): bool
    {
        return $this->is2FaRequired;
    }

    #[Override]
    public function formName(): string
    {
        return 'Login';
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'USER_EMAIL_LABEL'),
            'password' => Yii::t('skeleton', 'USER_PASSWORD_LABEL'),
            'code' => Yii::t('skeleton', 'USER_CODE_LABEL'),
            'rememberMe' => Yii::t('skeleton', 'USER_REMEMBERME_LABEL'),
        ];
    }
}
