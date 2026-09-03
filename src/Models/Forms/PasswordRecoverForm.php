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

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors()) {
            $this->validateUserStatus();
            $this->validateSpamProtection();
        }

        parent::afterValidate();
    }

    public function validateSpamProtection(): void
    {
        if ($this->isAlreadySent()) {
            $this->addError('email', Lang::t('skeleton', 'PASSWORD_RECOVER_WE_HAVE_JUST_SENT_A_LINK', [
                'email' => $this->user->email,
            ]));
        }
    }

    public function recover(): bool
    {
        if ($this->validate()) {
            $this->user->generatePasswordResetToken();
            $this->user->update();

            $this->sendPasswordResetEmail();
            return true;
        }

        return false;
    }

    public function sendPasswordResetEmail(): void
    {
        Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/recover', ['user' => $this->user])
            ->setSubject(Lang::t('skeleton', 'PASSWORD_RECOVER_RESET_YOUR_PASSWORD'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    public function isAlreadySent(): bool
    {
        return $this->user->password_reset_token
            && $this->user->updated_at->modify($this->timeoutSpamProtection) > new DateTime();
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
            'email' => Lang::t('skeleton', 'PASSWORD_RECOVER_EMAIL_LABEL'),
        ];
    }
}
