<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Override;
use Yii;
use yii\base\Model;

class PasswordResetForm extends Model
{
    use ModelTrait;
    use IdentityTrait;

    public ?string $code = null;
    public ?string $newPassword = null;
    public ?string $repeatPassword = null;

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
                $this->validateRepeatPassword(...),
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

    protected function validateRepeatPassword(): void
    {
        if ($this->repeatPassword !== $this->newPassword) {
            $this->addError('repeatPassword', Lang::t('skeleton', 'COMMON_THE_PASSWORD_MUST_MATCH_THE_NEW'));
        }
    }

    public function validatePasswordResetCode(): bool
    {
        if ($this->user->password_reset_token !== $this->code) {
            $this->addError('id', Lang::t('skeleton', 'PASSWORD_RESET_THE_PASSWORD_RECOVERY_URL_IS_INVALID'));
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
                ? Lang::t('skeleton', 'COMMON_NEW_PASSWORD')
                : Lang::t('skeleton', 'COMMON_PASSWORD'),
            'repeatPassword' => Lang::t('skeleton', 'PASSWORD_RESET_REPEATPASSWORD_LABEL'),
        ];
    }
}
