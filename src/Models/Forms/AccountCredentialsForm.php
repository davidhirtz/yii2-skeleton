<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Forms\Traits\UserFormTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use yii\base\Model;

class AccountCredentialsForm extends Model
{
    use ModelTrait;
    use UserFormTrait;

    public ?string $newPassword = null;
    public ?string $repeatPassword = null;
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
                'max' => $this->user->passwordMaxLength,
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
                'message' => Yii::t('skeleton', 'USER_REPEAT_PASSWORD_ERROR'),
            ],
            [
                ['oldPassword'],
                'required',
                'message' => Yii::t('skeleton', 'ACCOUNT_OLD_PASSWORD_ERROR'),
                'when' => fn (self $model): bool => $model->isOldPasswordRequired(),
            ],
            [
                ['oldPassword'],
                $this->validateOldPassword(...),
                'when' => fn (self $model): bool => $model->isOldPasswordRequired(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function getUserAttributeNames(): array
    {
        return ['email'];
    }

    /**
     * The page changes nothing but the email and the password, so the current password gates both.
     */
    public function isOldPasswordRequired(): bool
    {
        return (bool)$this->user->password_hash;
    }

    protected function beforeSave(): bool
    {
        if ($this->user->isAttributeChanged('email')) {
            $this->user->email_confirmed_at = null;
        }

        if ($this->newPassword) {
            $this->user->generateAuthKey();
            $this->user->generatePasswordHash($this->newPassword);
        }

        return true;
    }

    protected function validateOldPassword(): void
    {
        if (!$this->user->validatePassword((string)$this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    protected function afterSave(): void
    {
        if ($this->email !== $this->user->email) {
            $session = Application::current()->getSession();
            $webuser = Application::current()->getUser();

            if (!$webuser->isUnconfirmedEmailLoginEnabled()) {
                $webuser->logout(false);

                $session->addFlash('success', Yii::t('skeleton', 'ACCOUNT_EMAIL_SUCCESS'));
            }

            if (!$this->sendEmailConfirmationEmail()) {
                $session->addFlash('warning', Yii::t('skeleton', 'MAIL_SEND_ERROR', [
                    'email' => $this->user->email,
                ]));
            }
        }

        if ($this->newPassword) {
            $this->user->afterPasswordChange();
            Application::current()->getUser()->destroyOtherSessions($this->user);
            Application::current()->getUser()->resendIdentityCookie();
        }
    }

    /**
     * To the new address, since the link confirms it; `$email` is the one the user had before.
     */
    protected function sendEmailConfirmationEmail(): bool
    {
        return Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/email', [
            'form' => $this,
            'url' => $this->user->createEmailConfirmationUrl(),
        ])
            ->setSubject(Yii::t('skeleton', 'ACCOUNT_EMAIL_SUBJECT'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'newPassword' => Yii::t('skeleton', 'USER_NEW_PASSWORD_LABEL'),
            'repeatPassword' => Yii::t('skeleton', 'USER_REPEAT_PASSWORD_LABEL'),
            'oldPassword' => Yii::t('skeleton', 'USER_OLD_PASSWORD_LABEL'),
        ];
    }
}
