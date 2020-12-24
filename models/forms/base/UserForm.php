<?php

namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\web\UploadedFile;

/**
 * Class UserForm
 * @package davidhirtz\yii2\skeleton\models\forms\base
 *
 * @method static \davidhirtz\yii2\skeleton\models\forms\UserForm findOne($condition)
 */
class UserForm extends User
{
    /**
     * @var string
     */
    public $newPassword;

    /**
     * @var string
     */
    public $oldPassword;

    /**
     * @var string
     */
    public $oldEmail;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['email'],
                /** {@see \davidhirtz\yii2\skeleton\models\forms\UserForm::validateEmail()} */
                'validateEmail',
                'skipOnError' => true,
            ],
            [
                ['newPassword', 'oldPassword'],
                'filter',
                'filter' => 'trim',
            ],
            [
                ['newPassword', 'oldPassword'],
                'string',
                'min' => $this->passwordMinLength,
            ],
            [
                ['newPassword'],
                /** {@see \davidhirtz\yii2\skeleton\models\forms\UserForm::validateNewPassword()} */
                'validateNewPassword',
                'skipOnError' => true,
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function afterFind()
    {
        $this->oldEmail = $this->email;
        parent::afterFind();
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert): bool
    {
        if ($this->isAttributeChanged('email')) {
            $this->generateEmailConfirmationCode();
        }

        if ($this->newPassword) {
            $this->generatePasswordHash($this->newPassword);
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            $session = Yii::$app->getSession();

            if (array_key_exists('email', $changedAttributes)) {
                $user = Yii::$app->getUser();

                if (!$user->isUnconfirmedEmailLoginEnabled()) {
                    $user->logout(false);

                    if ($session) {
                        $session->addFlash('success', Yii::t('skeleton', 'Please check your emails to confirm your new email address!'));
                    }
                }

                $this->sendEmailConfirmationEmail();
            }

            if (isset($changedAttributes['password'])) {
                $this->afterPasswordChange($session ? $session->getId() : null);
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Validates old password on email change.
     */
    public function validateEmail()
    {
        if ($this->isAttributeChanged('email') && !$this->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    /**
     * Validates old password on password change.
     */
    public function validateNewPassword()
    {
        if ($this->newPassword && !$this->validatePassword($this->oldPassword)) {
            $this->addInvalidAttributeError('oldPassword');
        }
    }

    /**
     * Sends email confirmation mail.
     */
    public function sendEmailConfirmationEmail()
    {
        Yii::$app->getMailer()->compose('@skeleton/mail/account/email', ['user' => $this])
            ->setSubject(Yii::t('skeleton', 'Please confirm your new email address'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }

    /**
     * @return array
     */
    public function scenarios()
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
                'oldPassword',
                'timezone',
                'upload',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'newPassword' => Yii::t('skeleton', 'New password'),
            'oldPassword' => Yii::t('skeleton', 'Current password'),
        ]);
    }
}