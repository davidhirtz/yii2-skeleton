<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\Identity;
use Yii;

/**
 * Class IdentityTrait
 * @package davidhirtz\yii2\skeleton\models\traits
 */
trait IdentityTrait
{
    /**
     * @var string
     */
    public $email;

    /**
     * @var Identity
     */
    private $_user;

    /**
     * @return Identity
     */
    public function getUser()
    {
        if ($this->_user === null) {
            if ($this->email) {
                $this->_user = Identity::findByEmail($this->email)
                    ->selectIdentityAttributes()
                    ->limit(1)
                    ->one();
            }
        }

        return $this->_user;
    }

    /**
     * @param Identity $user
     */
    public function setUser($user)
    {
        if ($user instanceof Identity) {
            $this->_user = $user;
            $this->email = $user->email;
        }
    }

    /**
     * Validates that user was found by email.
     */
    public function validateUserEmail()
    {
        if (!$this->hasErrors() && !$this->getUser()) {
            $this->addError('id', Yii::t('skeleton', 'Your email was not found.'));
        }
    }

    /**
     * Validates user status, except for site owner.
     */
    public function validateUserStatus()
    {
        if (!$this->hasErrors() && ($user = $this->getUser()) && $user->isDisabled() && !$user->isOwner()) {
            $this->addError('status', Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
        }
    }
}