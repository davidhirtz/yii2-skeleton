<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

trait IdentityTrait
{
    public ?string $email = null;
    public ?User $user = null;

    protected function validateEmail(): void
    {
        $this->user ??= User::find()
            ->andWhereEmail($this->email)
            ->limit(1)
            ->one();

        if (null === $this->user) {
            $this->addError('email', Yii::t('skeleton', 'Your email was not found.'));
        }
    }

    protected function validateUserStatus(): void
    {
        if ($this->user->isDisabled() && !$this->user->isOwner()) {
            $this->addError('status', Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
        }
    }
}
