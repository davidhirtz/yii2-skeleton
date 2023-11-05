<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

class AccountResendConfirmForm extends Model
{
    use IdentityTrait;

    /**
     * @var string the interval in which no new email will be sent as date time string.
     */
    public string $timeoutSpamProtection = '5 mins';

    public function rules(): array
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
            [
                ['email'],
                $this->validateUserEmail(...),
            ],
        ];
    }

    public function afterValidate(): void
    {
        $this->validateUserStatus();
        $this->validateUserConfirmationCode();
        $this->validateSpamProtection();

        parent::afterValidate();
    }

    public function validateUserConfirmationCode(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && !$user->verification_token) {
            $this->addError('email', Yii::t('skeleton', 'Your account was already confirmed!'));
        }
    }

    public function validateSpamProtection(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $this->isAlreadySent()) {
            $this->addError('email', Yii::t('skeleton', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', [
                'email' => $user->email,
            ]));
        }
    }

    public function resend(): bool
    {
        if ($this->validate()) {
            $this->sendConfirmEmail();
            $this->getUser()->update();

            return true;
        }

        return false;
    }

    public function sendConfirmEmail(): void
    {
        if ($user = $this->getUser()) {
            Yii::$app->getMailer()->compose('@skeleton/mail/account/confirm', ['user' => $user])
                ->setSubject(Yii::t('skeleton', 'Confirm your account'))
                ->setFrom(Yii::$app->params['email'])
                ->setTo($user->email)
                ->send();
        }
    }

    public function isAlreadySent(): bool
    {
        return ($user = $this->getUser()) && $user->verification_token && $user->updated_at->modify($this->timeoutSpamProtection) > new DateTime();
    }

    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'Email'),
        ];
    }
}