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
            $this->addIdentityError(Yii::t('skeleton', 'USER_EMAIL_NOT_FOUND_ERROR'));
        }

        return !$this->hasErrors('email');
    }

    protected function validateUserStatus(): void
    {
        if ($this->user->isDisabled() && !$this->user->isOwner()) {
            $this->addIdentityError(Yii::t('skeleton', 'USER_DISABLED_ERROR'));
        }
    }

    /**
     * Reports `$message` only while the application reveals which addresses have an account, and otherwise the
     * shared message, or drops the identity where failing at all would give the answer away.
     */
    protected function addIdentityError(string $message): void
    {
        if (!$this->canRevealIdentity()) {
            $this->user = null;
            return;
        }

        $this->addError('email', WebUser::current()?->enableUserEnumerationProtection
            ? Yii::t('skeleton', 'LOGIN_CREDENTIALS_ERROR')
            : $message);
    }

    /**
     * `false` for a form a guest can submit for any address, whose failure alone would confirm the address exists.
     */
    protected function canRevealIdentity(): bool
    {
        return true;
    }
}
