<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Override;
use Yii;
use yii\base\Model;

class PasswordResetForm extends Model
{
    use IdentityTrait;

    public ?string $code = null;
    public ?string $newPassword = null;

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['email', 'code', 'newPassword', 'repeatPassword'],
                'required',
            ],
            [
                ['email'],
                $this->validateEmail(...),
            ],
            [
                ['code'],
                'string',
                'length' => 32,
            ],
            [
                ['newPassword'],
                'string',
                'min' => User::instance()->passwordMinLength,
            ],
            [
                ['repeatPassword'],
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Yii::t('skeleton', 'The password must match the new password.'),
            ],
        ];
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->validatePasswordResetCode();
            $this->validateUserStatus();
        }

        parent::afterValidate();
    }

    public function validatePasswordResetCode(): bool
    {
        if ($this->user->password_reset_token !== $this->code) {
            $this->addError('id', Yii::t('skeleton', 'The password recovery url is invalid.'));
        }

        return !$this->hasErrors();
    }

    /**
     * Hashes new password and logs in user if possible.
     * This method also deletes all cookie auth keys for this user, so auto login cookies are not working anymore.
     */
    public function reset(): bool|int
    {
        if (!$this->validate()) {
            return false;
        }

        $this->user->generateAuthKey();
        $this->user->generatePasswordHash($this->newPassword);
        $this->user->password_reset_token = null;
        $this->user->afterPasswordChange();

        $webuser = Yii::$app->getUser();

        if ($webuser->getIsGuest() && (!$this->user->isUnconfirmed() || $webuser->isUnconfirmedEmailLoginEnabled())) {
            $webuser->loginType = UserLogin::TYPE_RESET_PASSWORD;
            return $webuser->login($this->user);
        }

        return $this->user->update();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'newPassword' => $this->user?->login_count
                ? Yii::t('skeleton', 'New password')
                : Yii::t('skeleton', 'Password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
        ];
    }
}
