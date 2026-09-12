<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Override;
use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    use IdentityTrait;
    use ModelTrait;

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
                'when' => fn () => !$this->hasErrors(),
            ],
            [
                ['code'],
                'string',
                'length' => 6,
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
        if (!Yii::$app->getUser()->isLoginEnabled()) {
            $this->addError('email', Yii::t('skeleton', 'USER_SORRY_LOGGING_CURRENTLY'));
            return false;
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->validateUserStatus();
            $this->validateLoginStatus();
            $this->validateTwoFactorAuthenticatorCode();
        }

        parent::afterValidate();
    }

    protected function validatePassword(): void
    {
        if (!$this->user->validatePassword($this->password)) {
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
        if (Yii::$app->getUser()->enableTwoFactorAuthentication && $this->user->google_2fa_secret) {
            $validator = Yii::$container->get(TwoFactorAuthenticationValidator::class, [], [
                'secret' => $this->user->google_2fa_secret,
                'datetime' => $this->user->last_login,
            ]);

            $validator->validateAttribute($this, 'code');
            $this->is2FaRequired = true;
        }
    }

    public function login(): bool
    {
        if ($this->validate()) {
            $webuser = Yii::$app->getUser();
            $webuser->loginType = UserLogin::TYPE_LOGIN;

            $this->user->generatePasswordHash($this->password);

            return Yii::$app->getUser()->login($this->user, $this->rememberMe ? $webuser->cookieLifetime : 0);
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
