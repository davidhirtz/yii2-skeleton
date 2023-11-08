<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * UserForm extends {@see User}. It is used to update user information of the current webuser.
 */
class UserForm extends User
{
    public ?string $newPassword = null;
    public ?string $repeatPassword = null;
    public ?string $oldPassword = null;
    public ?string $oldEmail = null;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['email'],
                $this->validateEmail(...),
            ], [
                ['newPassword', 'repeatPassword', 'oldPassword'],
                'trim',
            ], [
                ['newPassword', 'repeatPassword', 'oldPassword'],
                'string',
                'min' => $this->passwordMinLength,
            ], [
                ['newPassword'],
                $this->validateNewPassword(...),
                'skipOnError' => true,
            ], [
                ['repeatPassword'],
                'required',
                'when' => fn (self $model): bool => (bool)$model->newPassword,
            ], [
                ['repeatPassword'],
                'compare',
                'compareAttribute' => 'newPassword',
                'message' => Yii::t('skeleton', 'The password must match the new password.'),
            ]
        ];
    }

    public function afterFind(): void
    {
        $this->oldEmail = $this->email;
        parent::afterFind();
    }

    /**
     * @param bool $insert
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($this->isAttributeChanged('email')) {
                $this->generateVerificationToken();
            }

            if ($this->newPassword) {
                $this->generateAuthKey();
                $this->generatePasswordHash($this->newPassword);
            }

            return true;
        }

        return false;
    }

    
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert) {
            $session = Yii::$app->getSession();

            if (array_key_exists('email', $changedAttributes)) {
                $user = Yii::$app->getUser();

                if (!$user->isUnconfirmedEmailLoginEnabled()) {
                    $user->logout(false);

                    $session?->addFlash('success', Yii::t('skeleton', 'Please check your emails to confirm your new email address!'));
                }

                $this->sendEmailConfirmationEmail();
            }

            if (array_key_exists('password_hash', $changedAttributes)) {
                $this->afterPasswordChange();
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function validateEmail(): void
    {
        if ($this->isAttributeChanged('email') && !$this->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    public function validateNewPassword(): void
    {
        if ($this->newPassword && !$this->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    /**
     * Sends email confirmation mail.
     */
    public function sendEmailConfirmationEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/mail/account/email', ['user' => $this])
            ->setSubject(Yii::t('skeleton', 'Please confirm your new email address'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }

    
    public function scenarios(): array
    {
        return [
            static::SCENARIO_DEFAULT => [
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
                'upload',
            ],
        ];
    }

    
    public function attributeLabels(): array
    {
        return [...parent::attributeLabels(), 'newPassword' => Yii::t('skeleton', 'New password'), 'repeatPassword' => Yii::t('skeleton', 'Repeat password'), 'oldPassword' => Yii::t('skeleton', 'Current password')];
    }
}
