<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\behaviors\SerializedAttributesBehavior;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * Class AuthClient.
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $id
 * @property integer $user_id
 * @property string $name
 * @property array $data
 * @property \davidhirtz\yii2\datetime\DateTime $updated_at
 * @property \davidhirtz\yii2\datetime\DateTime $created_at
 *
 * @property User $user
 */
class AuthClient extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
	/***********************************************************************
	 * Behaviors.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'SerializedAttributesBehavior'=>[
				'class'=>SerializedAttributesBehavior::class,
				'attributes'=>['data'],
			],
			'TimestampBehavior'=>[
				'class'=>TimestampBehavior::class,
				'value'=>function(){return new DateTime;},
			],
			'DateTimeBehavior'=>[
				'class'=>DateTimeBehavior::class,
			],
		];
	}

	/***********************************************************************
	 * Relations.
	 ***********************************************************************/

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(User::class, ['id'=>'user_id']);
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return string
	 */
	public function getDisplayName()
	{
		/**
		 * @var \davidhirtz\yii2\skeleton\auth\clients\ClientInterface $client
		 */
		$client=Yii::$app->authClientCollection->getClient($this->name);
		return $client::getDisplayName($this);
	}

	/**
	 * @return string
	 */
	public function getExternalUrl()
	{
		/**
		 * @var \davidhirtz\yii2\skeleton\auth\clients\ClientInterface $client
		 */
		$client=Yii::$app->authClientCollection->getClient($this->name);
		return $client::getExternalUrl($this);
	}

	/**
	 * @return \davidhirtz\yii2\skeleton\auth\clients\ClientInterface|\yii\authclient\ClientInterface
	 */
	public function getClientClass()
	{
		return Yii::$app->authClientCollection->getClient($this->name);
	}

	/***********************************************************************
	 * Active Record.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_client}}';
	}
}
