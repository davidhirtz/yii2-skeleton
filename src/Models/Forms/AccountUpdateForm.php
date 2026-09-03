<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Forms\Traits\UserFormTrait;
use Hirtz\Skeleton\Models\User;
use Override;
use Yii;
use yii\base\Model;

class AccountUpdateForm extends Model
{
    use ModelTrait;
    use UserFormTrait;

    public ?string $newPassword = null;
    public ?string $oldPassword = null;
    public readonly ?string $email;

    public function __construct(public User $user, array $config = [])
    {
        $this->email = $user->email;
        parent::__construct($config);
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['newPassword', 'repeatPassword', 'oldPassword'],
                'trim',
            ],
            [
                ['newPassword', 'repeatPassword'],
                'string',
                'min' => $this->user->passwordMinLength,
            ],
            [
                ['repeatPassword'],
                'required',
                'when' => fn (self $model): bool => (bool)$model->newPassword,
            ],
            [
                ['repeatPassword'],
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Lang::t('skeleton', 'COMMON_THE_PASSWORD_MUST_MATCH_THE_NEW'),
            ],
            [
                ['oldPassword'],
                $this->validateOldPassword(...),
                'message' => Lang::t('skeleton', 'ACCOUNT_UPDATE_YOUR_CURRENT_PASSWORD_IS_REQUIRED_TO'),
                'skipOnEmpty' => false,
                'when' => fn (self $model): bool => $model->newPassword || $model->email !== $model->user->email,
            ]
        ];
    }

    protected function beforeSave(): bool
    {
        if ($this->user->isAttributeChanged('email')) {
            $this->user->generateVerificationToken();
        }

        if ($this->newPassword) {
            $this->user->generateAuthKey();
            $this->user->generatePasswordHash($this->newPassword);
        }

        return true;
    }

    protected function validateOldPassword(): void
    {
        if (!$this->user->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    protected function afterSave(): void
    {
        if ($this->email !== $this->user->email) {
            $session = Yii::$app->getSession();
            $webuser = Yii::$app->getUser();

            if (!$webuser->isUnconfirmedEmailLoginEnabled()) {
                $webuser->logout(false);

                $session->addFlash('success', Lang::t('skeleton', 'ACCOUNT_UPDATE_FLASH_PLEASE_CHECK_YOUR_EMAILS_TO_CONFIRM'));
            }

            $this->sendEmailConfirmationEmail();
        }

        if ($this->newPassword) {
            $this->user->afterPasswordChange();
        }
    }

    protected function sendEmailConfirmationEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/email', ['form' => $this])
            ->setSubject(Lang::t('skeleton', 'ACCOUNT_UPDATE_PLEASE_CONFIRM_YOUR_NEW_EMAIL_ADDRESS'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'newPassword' => Lang::t('skeleton', 'ACCOUNT_UPDATE_NEWPASSWORD_LABEL'),
            'repeatPassword' => Lang::t('skeleton', 'ACCOUNT_UPDATE_REPEATPASSWORD_LABEL'),
            'oldPassword' => Lang::t('skeleton', 'ACCOUNT_UPDATE_OLDPASSWORD_LABEL'),
        ];
    }
}
