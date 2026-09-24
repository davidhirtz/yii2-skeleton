<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Traits\IdentityTrait;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use davidhirtz\yii2\datetime\DateTime;
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
        return !WebUser::current()?->enableUserEnumerationProtection;
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

        // An address with no account, a disabled one, one that was just sent a link and, under enumeration
        // protection, one whose email failed all report the same success, so nothing here says which exist.
        if ($this->user && !$this->sendPasswordResetEmail()) {
            // Without the token of the failed attempt the spam protection lets a retry through.
            $this->user->getLatestToken(UserToken::TYPE_PASSWORD_RESET)?->delete();
            $this->addIdentityError(Yii::t('skeleton', 'COMMON_ERROR_EMAIL_NOT_SENT', [
                'email' => $this->user->email,
            ]));
        }

        return !$this->hasErrors();
    }

    public function sendPasswordResetEmail(): bool
    {
        return Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/recover', [
            'user' => $this->user,
            'url' => $this->user->createPasswordResetUrl(),
        ])
            ->setSubject(Yii::t('skeleton', 'PASSWORD_RECOVER_RESET_YOUR_PASSWORD'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }

    public function isAlreadySent(): bool
    {
        $token = $this->user->getLatestToken(UserToken::TYPE_PASSWORD_RESET);
        return $token && $token->created_at->modify($this->timeoutSpamProtection) > new DateTime();
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
