<?php
namespace davidhirtz\yii2\skeleton\models\forms\user;
use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * Class UserForm.
 * @package davidhirtz\yii2\skeleton\models\forms\user
 */
class UserForm extends User
{
	/**
	 * @var string
	 */
	public $newPassword;

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return array_merge(parent::rules(), [
			[
				['timezone'],
				'required',
			],
			[
				['upload'],
				'file',
				'extensions'=>'gif, jpg, jpeg, png',
			],
		]);
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * Saves uploaded picture.
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		if(!$insert)
		{
			if(isset($changedAttributes['password']))
			{
				$this->deleteAuthKeys();
				$this->deleteActiveSessions(Yii::$app->getSession()->getId());
			}
		}

		parent::afterSave($insert, $changedAttributes);
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return array
	 */
	public function getGenderOptions()
	{
		return [
			static::GENDER_UNKNOWN=>'',
			static::GENDER_MALE=> Yii::t('app', 'Male'),
			static::GENDER_FEMALE=>Yii::t('app', 'Female'),
		];
	}

	/***********************************************************************
	 * Active Record.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'newPassword'=>Yii::t('app', 'New password'),
			'upload'=>Yii::t('app', 'Picture'),
		]);
	}
}