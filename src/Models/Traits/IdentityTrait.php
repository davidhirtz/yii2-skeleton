<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\User;
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
            $this->addError('email', Yii::t('skeleton', 'IDENTITY_YOUR_EMAIL_WAS_NOT_FOUND'));
        }

        return !$this->hasErrors('email');
    }

    protected function validateUserStatus(): void
    {
        if ($this->user->isDisabled() && !$this->user->isOwner()) {
            $this->addError('email', Yii::t('skeleton', 'COMMON_ACCOUNT_CURRENTLY_DISABLED'));
        }
    }
}
