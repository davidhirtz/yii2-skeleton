<?php
namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\db\Identity;

/**
 * Class IdentityTrait.
 * @package davidhirtz\yii2\skeleton\models\traits
 */
trait IdentityTrait
{
	/**
	 * @var Identity
	 */
	private $_user=null;

	/**
	 * @return Identity
	 */
	public function getUser()
	{
		if($this->_user===null)
		{
			$this->_user=Identity::findByEmail($this->email)
				->selectIdentityAttributes()
				->limit(1)
				->one();
		}

		return $this->_user;
	}

	/**
	 * @param Identity $user
	 */
	public function setUser($user)
	{
		if($user instanceof Identity)
		{
			$this->_user=$user;
			$this->email=$user->email;
		}
	}
}