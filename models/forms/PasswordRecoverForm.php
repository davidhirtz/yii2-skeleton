<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * @property User $user
 */
class PasswordRecoverForm extends Model
{
    use IdentityTrait;

    /**
     * @var string the interval in which no new email will be sent as date time string.
     */
    public $timeoutSpamProtection = '5 mins';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['email'],
                'trim',
            ],
            [
                ['email'],
                'required',
            ],
            [
                ['email'],
                'email',
            ],
        ];
    }

    /**
     * Validates user credentials and checks for spam protection.
     */
    public function afterValidate()
    {
        $this->validateUserEmail();
        $this->validateUserStatus();
        $this->validateSpamProtection();

        parent::afterValidate();
    }

    /**
     * Validates spam protection.
     */
    public function validateSpamProtection()
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $this->isAlreadySent()) {
            $this->addError('email', Yii::t('skeleton', 'We have just sent a link to reset your password to {email}. Please check your inbox!', [
                'email' => $user->email,
            ]));
        }
    }

    /**
     * @return bool
     */
    public function recover(): bool
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->generatePasswordResetToken();
            $user->update();

            $this->sendPasswordResetEmail();
            return true;
        }

        return false;
    }

    /**
     * Sends password reset code email.
     */
    public function sendPasswordResetEmail(): void
    {
        if ($user = $this->getUser()) {
            Yii::$app->getMailer()->compose('@skeleton/mail/account/recover', ['user' => $user])
                ->setSubject(Yii::t('skeleton', 'Reset your password'))
                ->setFrom(Yii::$app->params['email'])
                ->setTo($user->email)
                ->send();
        }
    }

    /**
     * @return bool
     */
    public function isAlreadySent(): bool
    {
        return ($user = $this->getUser()) && $user->password_reset_token && $user->updated_at->modify($this->timeoutSpamProtection) > new DateTime();
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'Email'),
        ];
    }
}