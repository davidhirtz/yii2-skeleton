<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Override;
use Yii;
use yii\base\Model;

class PasswordRecoverForm extends Model
{
    use ModelTrait;
    use IdentityTrait;

    /**
     * @var string the interval in which no new email will be sent as date time string.
     */
    public string $timeoutSpamProtection = '5 mins';

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

    protected function canRevealIdentity(): bool
    {
        return !Yii::$app->getUser()->enableUserEnumerationProtection;
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors() && $this->user) {
            $this->validateUserStatus();
        }

        if (!$this->hasErrors() && $this->user) {
            $this->validateSpamProtection();
        }

        parent::afterValidate();
    }

    public function validateSpamProtection(): void
    {
        if ($this->isAlreadySent()) {
            $this->addIdentityError(Yii::t('skeleton', 'PASSWORD_RECOVER_WE_JUST', [
                'email' => $this->user->email,
            ]));
        }
    }

    public function recover(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        // An address with no account, a disabled one, and one that was just sent a link all report the same
        // success as one that gets the email, so nothing here says which addresses exist.
        if ($this->user) {
            $this->user->generatePasswordResetToken();
            $this->user->update();

            $this->sendPasswordResetEmail();
        }

        return true;
    }

    public function sendPasswordResetEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/recover', ['user' => $this->user])
            ->setSubject(Yii::t('skeleton', 'PASSWORD_RECOVER_RESET_YOUR_PASSWORD'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    public function isAlreadySent(): bool
    {
        return $this->user->password_reset_token
            && $this->user->password_reset_token_created_at?->modify($this->timeoutSpamProtection) > new DateTime();
    }

    #[\Override]
    public function formName(): string
    {
        return 'PasswordRecover';
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('skeleton', 'PASSWORD_RECOVER_EMAIL_LABEL'),
        ];
    }
}
