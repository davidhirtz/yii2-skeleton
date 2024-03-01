<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use Yii;
use yii\base\Model;

/**
 * @property User $user {@see PasswordResetForm::getUser()}
 */
class PasswordResetForm extends Model
{
    use IdentityTrait;

    public ?string $code = null;
    public ?string $newPassword = null;
    public ?string $repeatPassword = null;

    public function rules(): array
    {
        return [
            [
                ['email', 'code', 'newPassword', 'repeatPassword'],
                'trim',
            ],
            [
                ['code'],
                'string',
                'length' => 32,
            ],
            [
                ['newPassword', 'repeatPassword'],
                'required',
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

    public function afterValidate(): void
    {
        $this->validatePasswordResetCode();
        $this->validateUserStatus();

        parent::afterValidate();
    }

    /**
     * Validates password reset code if user was found by email.
     */
    public function validatePasswordResetCode(): bool
    {
        if (!$this->hasErrors() && (!($user = $this->getUser()) || $user->password_reset_token != $this->code)) {
            $this->addError('id', Yii::t('skeleton', 'The password recovery url is invalid.'));
        }

        return !$this->hasErrors();
    }

    /**
     * Hashes new password and logs in user if possible.
     * This method also deletes all cookie auth keys for this user, so auto login cookies are not working anymore.
     *
     * The login takes care of updating the user record.
     */
    public function reset(): bool|int
    {
        if ($this->validate()) {
            $webuser = Yii::$app->getUser();
            $user = $this->getUser();

            $user->generateAuthKey();
            $user->generatePasswordHash($this->newPassword);
            $user->password_reset_token = null;
            $user->afterPasswordChange();

            if ($webuser->getIsGuest() && (!$user->isUnconfirmed() || $webuser->isUnconfirmedEmailLoginEnabled())) {
                $webuser->loginType = UserLogin::TYPE_RESET_PASSWORD;
                return $webuser->login($user);
            }

            return $user->update();
        }

        return false;
    }


    public function attributeLabels(): array
    {
        $user = $this->getUser();

        return [
            'newPassword' => $user?->login_count
                ? Yii::t('skeleton', 'New password')
                : Yii::t('skeleton', 'Password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
        ];
    }
}
