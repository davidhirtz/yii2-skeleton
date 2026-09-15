<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\User as WebUser;
use Yii;

trait IdentityTrait
{
    public ?string $email = null;
    public ?User $user = null;

    public function validateEmail(): bool
    {
        $this->user ??= User::find()
            ->andWhereEmail($this->email)
            ->limit(1)
            ->one();

        if (null === $this->user) {
            $this->addIdentityError(Yii::t('skeleton', 'IDENTITY_YOUR_EMAIL_WAS_NOT_FOUND'));
        }

        return !$this->hasErrors('email');
    }

    protected function validateUserStatus(): void
    {
        if ($this->user->isDisabled() && !$this->user->isOwner()) {
            $this->addIdentityError(Yii::t('skeleton', 'COMMON_ACCOUNT_CURRENTLY_DISABLED'));
        }
    }

    /**
     * Reports `$message` while the application is willing to say which addresses have an account, and otherwise
     * either replaces it with the one message every failure shares, or — for a form that would give the answer
     * away by failing at all — drops the identity so the caller reports its ordinary success.
     */
    protected function addIdentityError(string $message): void
    {
        if (!$this->canRevealIdentity()) {
            $this->user = null;
            return;
        }

        $this->addError('email', WebUser::current()?->enableUserEnumerationProtection
            ? Yii::t('skeleton', 'USER_EMAIL_PASSWORD_INCORRECT')
            : $message);
    }

    /**
     * `false` for a form whose failure alone would confirm that an address exists — the password recovery and the
     * confirmation resend, which a guest can submit for any address they like.
     */
    protected function canRevealIdentity(): bool
    {
        return true;
    }
}
