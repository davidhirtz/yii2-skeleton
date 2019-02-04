<?php
namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\models\User;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use Yii;

/**
 * Class Identity.
 * @package davidhirtz\yii2\skeleton\db
 */
class Identity extends User implements IdentityInterface
{
	/**
	 * @var int
	 */
	public $loginType;

	/**
	 * @var int
	 */
	public $ip;

	/**
	 * @var int
	 */
	public $cookieLifetime=2592000;

	/**
	 * @var int default 90 days
	 */
	public $authKeyLifetime=776000;

	/***********************************************************************
	 * Identity interface.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id)
	{
		/**
		 * @var Identity $identity
		 */
		$identity=static::find()
			->where(['id'=>$id, 'status'=>self::STATUS_ENABLED])
			->selectIdentityAttributes()
			->one();

		if($identity)
		{
			Yii::$app->timeZone=$identity->timezone;
		}

		return $identity;
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentityByAccessToken($token, $type=null)
	{
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}

	/**
	 * @inheritdoc
	 */
	public function getId()
	{
		return $this->getPrimaryKey();
	}

	/**
	 * @inheritdoc
	 */
	public function getAuthKey()
	{
		$columns=[
			'id'=>Yii::$app->getSecurity()->generateRandomString(64),
			'user_id'=>$this->id,
			'expire'=>time()+$this->authKeyLifetime,
		];

		Yii::$app->getDb()->createCommand()->insert('session_auth_key', $columns)->execute();
		return $columns['id'];
	}

	/**
	 * @inheritdoc
	 */
	public function validateAuthKey($authKey)
	{
		return Yii::$app->getDb()->createCommand()
			->delete('session_auth_key', '`id`=:id AND `user_id`=:userId AND `expire`>:expired', [':id'=>$authKey, ':userId'=>$this->id, ':expired'=>time()])
			->execute();
	}
}