<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\I18n\Lang;
use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
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
            $this->addError('email', Lang::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_ACCOUNT'));
        }
    }

    protected function validateSpamProtection(): void
    {
        if ($this->isAlreadySent()) {
            $this->addError('email', Lang::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_WE', [
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
        Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/confirm', ['user' => $this->user])
            ->setSubject(Lang::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_CONFIRM_YOUR_ACCOUNT'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    protected function isAlreadySent(): bool
    {
        return $this->user->verification_token
            && $this->user->updated_at?->modify($this->timeoutSpamProtection) > new DateTime();
    }

    #[\Override]
    public function formName(): string
    {
        return 'Account';
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'email' => Lang::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_EMAIL_LABEL'),
        ];
    }
}
