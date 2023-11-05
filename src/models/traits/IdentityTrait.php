<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\Identity;
use Yii;

/**
 * @property Identity $user
 */
trait IdentityTrait
{
    public ?string $email = null;
    private ?Identity $_user = null;

    public function getUser(): ?Identity
    {
        if ($this->email) {
            $this->_user ??= Identity::findByEmail($this->email)
                ->selectIdentityAttributes()
                ->limit(1)
                ->one();
        }

        return $this->_user;
    }

    public function setUser(Identity $user): void
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