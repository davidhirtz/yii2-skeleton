<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use davidhirtz\yii2\datetime\DateTime;
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

    protected function canRevealIdentity(): bool
    {
        return !Application::current()->getUser()->enableUserEnumerationProtection;
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->hasErrors() && $this->user) {
            $this->validateUserStatus();
        }

        if (!$this->hasErrors() && $this->user) {
            $this->validateUserConfirmationCode();
        }

        if (!$this->hasErrors() && $this->user) {
            $this->validateSpamProtection();
        }

        parent::afterValidate();
    }

    protected function validateUserConfirmationCode(): void
    {
        if (!$this->user->isUnconfirmed()) {
            $this->addIdentityError(Yii::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_ACCOUNT'));
        }
    }

    protected function validateSpamProtection(): void
    {
        if ($this->isAlreadySent()) {
            $this->addIdentityError(Yii::t('skeleton', 'ACCOUNT_ERROR_RESEND_ALREADY_SENT', [
                'email' => $this->user->email,
            ]));
        }
    }

    public function resend(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        // An address with nothing to confirm and, under enumeration protection, one whose email failed report the
        // same success, so nothing here says which exist.
        if ($this->user && !$this->sendConfirmEmail()) {
            // Without the token of the failed attempt the spam protection lets a retry through.
            $this->user->getLatestToken(UserToken::TYPE_VERIFICATION)?->delete();
            $this->addIdentityError(Yii::t('skeleton', 'COMMON_ERROR_EMAIL_NOT_SENT', [
                'email' => $this->user->email,
            ]));
        }

        return !$this->hasErrors();
    }

    protected function sendConfirmEmail(): bool
    {
        return Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/confirm', [
            'user' => $this->user,
            'url' => $this->user->createEmailConfirmationUrl(),
        ])
            ->setSubject(Yii::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_CONFIRM_YOUR_ACCOUNT'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    protected function isAlreadySent(): bool
    {
        $token = $this->user->getLatestToken(UserToken::TYPE_VERIFICATION);
        return $token && $token->created_at->modify($this->timeoutSpamProtection) > new DateTime();
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
            'email' => Yii::t('skeleton', 'ACCOUNT_RESEND_CONFIRM_EMAIL_LABEL'),
        ];
    }
}
