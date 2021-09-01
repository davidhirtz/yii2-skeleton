<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * Class AccountResendConfirmForm
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class AccountResendConfirmForm extends Model
{
    use IdentityTrait;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string the interval in which no new email will be sent as date time string.
     */
    public $timeoutSpamProtection = '5 mins';

    /**
     * @inheritdDoc
     */
    public function rules(): array
    {
        return [
            [
                ['email'],
                'filter',
                'filter' => 'trim',
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
    public function afterValidate(): void
    {
        $this->validateUserEmail();
        $this->validateUserStatus();
        $this->validateUserConfirmationCode();
        $this->validateSpamProtection();

        parent::afterValidate();
    }

    /**
     * Validates user credentials.
     */
    public function validateUserConfirmationCode(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && !$user->email_confirmation_code) {
            $this->addError('email', Yii::t('skeleton', 'Your account was already confirmed!'));
        }
    }

    /**
     * Validates spam protection.
     */
    public function validateSpamProtection(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $this->isAlreadySent()) {
            $this->addError('email', Yii::t('skeleton', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', [
                'email' => $user->email,
            ]));
        }
    }

    /**
     * @return bool
     */
    public function resend(): bool
    {
        if ($this->validate()) {
            $this->sendConfirmEmail();
            $this->getUser()->update();

            return true;
        }

        return false;
    }

    /**
     * Sends email confirm code email.
     */
    public function sendConfirmEmail()
    {
        if ($user = $this->getUser()) {
            Yii::$app->getMailer()->compose('@skeleton/mail/account/confirm', ['user' => $user])
                ->setSubject(Yii::t('skeleton', 'Confirm your account'))
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
        return ($user = $this->getUser()) && $user->email_confirmation_code && $user->updated_at->modify($this->timeoutSpamProtection) > new DateTime();
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