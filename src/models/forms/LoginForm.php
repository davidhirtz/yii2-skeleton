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

    public bool $enableFacebookLogin = true;
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
            $this->addError('id', Yii::t('skeleton', 'Sorry, logging in is currently disabled!'));
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
            $this->addError('email', Yii::t('skeleton', 'Your email or password are incorrect.'));
        }
    }

    protected function validateLoginStatus(): void
    {
        if ($this->user->isUnconfirmed() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
            $this->addError('status', Yii::t('skeleton', 'Your email address is not confirmed yet. You should find a confirmation email in your inbox.'));
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

    public function isFacebookLoginEnabled(): bool
    {
        return $this->enableFacebookLogin && Yii::$app->getAuthClientCollection()->hasClient('facebook');
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
            'email' => Yii::t('skeleton', 'Email'),
            'password' => Yii::t('skeleton', 'Password'),
            'code' => Yii::t('skeleton', 'Code'),
            'rememberMe' => Yii::t('skeleton', 'Keep me logged in'),
        ];
    }
}
