<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use Override;
use Yii;
use yii\base\Model;

class AccountResendConfirmForm extends Model
{
    use IdentityTrait;
    use ModelTrait;

    /**
     * @var string the interval in which no new email will be sent as date time string.
     */
    public string $timeoutSpamProtection = '1 min';

    #[Override]
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
                $this->validateEmail(...),
            ],
        ];
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->validateUserStatus();
            $this->validateUserConfirmationCode();
            $this->validateSpamProtection();
        }

        parent::afterValidate();
    }

    protected function validateUserConfirmationCode(): void
    {
        if (!$this->user->verification_token) {
            $this->addError('email', Yii::t('skeleton', 'Your account was already confirmed!'));
        }
    }

    protected function validateSpamProtection(): void
    {
        if ($this->isAlreadySent()) {
            $this->addError('email', Yii::t('skeleton', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', [
                'email' => $this->user->email,
            ]));
        }
    }

    public function resend(): bool
    {
        if ($this->validate()) {
            $this->sendConfirmEmail();

            $this->user->updateAttributes([
                'updated_at' => new DateTime(),
            ]);

            return true;
        }

        return false;
    }

    protected function sendConfirmEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/mail/account/confirm', ['user' => $this->user])
            ->setSubject(Yii::t('skeleton', 'Confirm your account'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    protected function isAlreadySent(): bool
    {
        return $this->user->verification_token
            && $this->user->updated_at?->modify($this->timeoutSpamProtection) > new DateTime();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'Email'),
        ];
    }
}
