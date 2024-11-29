<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\forms\traits\UserFormTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

class AccountUpdateForm extends Model
{
    use ModelTrait;
    use UserFormTrait;

    public ?string $newPassword = null;
    public ?string $oldPassword = null;
    public ?string $oldEmail = null;

    public function __construct(public User $user, array $config = [])
    {
        $this->oldEmail = $user->email;
        $this->setAttributesFromUser();

        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [
                ['name', 'email', 'newPassword', 'repeatPassword', 'oldPassword'],
                'trim',
            ],
            [
                ['email'],
                $this->validateEmail(...),
            ],
            [
                ['newPassword', 'repeatPassword'],
                'string',
                'min' => $this->user->passwordMinLength,
            ],
            [
                ['newPassword'],
                $this->validateNewPassword(...),
                'skipOnError' => true,
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
                'message' => Yii::t('skeleton', 'The password must match the new password.'),
            ],
        ];
    }

    public function update(): bool
    {
        if (!$this->validate() || !$this->beforeUpdate()) {
            return false;
        }

        if ($this->user->update(false)) {
            $this->afterUpdate();
            return true;
        }

        return false;
    }

    public function beforeUpdate(): bool
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

    public function afterUpdate(): void
    {
        $this->setAttributesFromUser();

        if ($this->oldEmail !== $this->user->email) {
            $session = Yii::$app->getSession();
            $webuser = Yii::$app->getUser();

            if (!$webuser->isUnconfirmedEmailLoginEnabled()) {
                $webuser->logout(false);

                $session->addFlash('success', Yii::t('skeleton', 'Please check your emails to confirm your new email address!'));
            }

            $this->sendEmailConfirmationEmail();
        }

        if ($this->newPassword) {
            $this->user->afterPasswordChange();
        }
    }

    public function validateEmail(): void
    {
        if ($this->user->isAttributeChanged('email') && !$this->user->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    public function validateNewPassword(): void
    {
        if ($this->newPassword && !$this->user->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    public function sendEmailConfirmationEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/mail/account/email', ['form' => $this])
            ->setSubject(Yii::t('skeleton', 'Please confirm your new email address'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }

    public function scenarios(): array
    {
        return [
            ActiveRecord::SCENARIO_DEFAULT => [
                'city',
                'country',
                'email',
                'first_name',
                'language',
                'last_name',
                'name',
                'newPassword',
                'repeatPassword',
                'oldPassword',
                'timezone',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...$this->user->attributeLabels(),
            'newPassword' => Yii::t('skeleton', 'New password'),
            'repeatPassword' => Yii::t('skeleton', 'Repeat password'),
            'oldPassword' => Yii::t('skeleton', 'Current password'),
        ];
    }
}
