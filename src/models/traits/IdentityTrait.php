<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * @property User $user {@see static::getUser()}
 */
trait IdentityTrait
{
    public ?string $email = null;
    private ?User $user = null;

    public function getUser(): ?User
    {
        if (null !== $this->email) {
            $this->user ??= User::find()
                ->andWhereEmail($this->email)
                ->limit(1)
                ->one();
        }

        return $this->user;
    }

    public function user(?User $user): static
    {
        $this->user = $user;
        $this->email = $user?->email;

        return $this;
    }

    public function validateUserEmail(): void
    {
        if (!$this->hasErrors() && !$this->getUser()) {
            $this->addError('email', Yii::t('skeleton', 'Your email was not found.'));
        }
    }

    public function validateUserStatus(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $user->isDisabled() && !$user->isOwner()) {
            $this->addError('status', Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
        }
    }
}
