<?php
namespace davidhirtz\yii2\skeleton\auth\rbac;

use yii\helpers\ArrayHelper;
use yii\rbac\Rule;

/**
 * Class OwnerRule.
 * @package davidhirtz\yii2\skeleton\auth\rbac
 */
class OwnerRule extends Rule
{
	/**
	 * @var string
	 */
	public $name='userUpdateRule';

	/**
	 * @inheritdoc
	 */
	public function execute($userId, $item, $params)
	{
		/**
		 * @var \davidhirtz\yii2\skeleton\models\User $user
		 */
		$user=ArrayHelper::getValue($params, 'user');
		return $user===null || !$user->getIsOwner() || $user->id==$userId;
	}
}