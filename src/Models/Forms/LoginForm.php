<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
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

    public ?string $password = null;
    public ?string $code = null;
    public bool|string $rememberMe = true;

    private bool $is2FaRequired = false;

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
            ],
            [
                ['email'],
                'email',
            ],
            [
                ['email'],
                $this->validateEmail(...),
                'when' => fn () => !$this->hasErrors(),
            ],
            [
                ['password'],
                $this->validatePassword(...),
                'when' => fn () => !$this->hasErrors('password'),
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
        $webuser = Yii::$app->getUser();

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
        if ($this->user->isUnconfirmed() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->addError('status', Yii::t('skeleton', 'USER_EMAIL_ADDRESS_NOT'));
        }
    }

    protected function validateTwoFactorAuthenticatorCode(): void
    {
        if (!Yii::$app->getUser()->isTwoFactorAuthenticationRequired($this->user)) {
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
        $webuser = Yii::$app->getUser();

        if ($this->validate()) {
            $webuser->loginType = UserLogin::TYPE_LOGIN;
            $webuser->resetFailedLoginAttempts($this->email);

            if ($this->user->isPasswordHashOutdated()) {
                $this->user->generatePasswordHash($this->password);
            }

            return $webuser->login($this->user, $this->rememberMe ? $webuser->cookieLifetime : 0);
        }

        // A blank form is a mistake, not an attempt — only a submission that got as far as a credential counts.
        if ($this->email && $this->password) {
            $webuser->addFailedLoginAttempt($this->email);
        }

        if (null === $this->code) {
            $this->clearErrors('code');
        }

        return false;
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
