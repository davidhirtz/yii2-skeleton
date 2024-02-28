<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * @property User $user {@see static::getUser()}
 */
trait IdentityTrait
{
    public ?string $email = null;
    private ?User $_user = null;

    public function getUser(): ?User
    {
        if ($this->email) {
            $this->_user ??= User::find()
                ->andWhereEmail($this->email)
                ->limit(1)
                ->one();
        }

        return $this->_user;
    }

    public function setUser(User $user): void
    {
        $this->_user = $user;
        $this->email = $user->email;
    }

    public function validateUserEmail(): void
    {
        if (!$this->hasErrors() && !$this->getUser()) {
            $this->addError('id', Yii::t('skeleton', 'Your email was not found.'));
        }
    }

    public function validateUserStatus(): void
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $user->isDisabled() && !$user->isOwner()) {
            $this->addError('status', Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
        }
    }
}
