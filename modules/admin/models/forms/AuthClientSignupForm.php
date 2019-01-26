<?php
namespace app\models\forms\user;
use davidhirtz\yii2\skeleton\db\Identity;
use Yii;
use yii\behaviors\SluggableBehavior;
use yii\db\AfterSaveEvent;

/**
 * Class AuthClientSignupForm.
 * @package app\models\forms\user
 */
class AuthClientSignupForm extends Identity
{
	/**
	 * Traits.
	 */
	use \app\models\traits\user\SignupEmailTrait;

	/**
	 * @var string
	 */
	public $externalPictureUrl;

	/***********************************************************************
	 * Init.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->on(static::EVENT_AFTER_INSERT, [$this, 'onAfterInsert']);
		$this->on(static::EVENT_AFTER_INSERT, [$this, 'sendSignupEmail']);
		parent::init();
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array_merge(parent::behaviors(), [
			[
				'class'=>SluggableBehavior::class,
				'attribute'=>'name',
				'slugAttribute'=>'name',
			],
		]);
	}

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @return array
	 */
	public function rules()
	{
		return array_merge(parent::rules(), [
			[
				['externalPictureUrl'],
				'validateExternalPictureUrl',
			],
		]);
	}

	/**
	 * @see \app\models\forms\user\AuthSignupForm::rules()
	 * @todo
	 */
	public function validateExternalPictureUrl()
	{
//		if($this->externalPictureUrl)
//		{
//		}
	}

	/**
	 * Sets username, gender and language defaults.
	 * @return bool
	 */
	public function beforeValidate()
	{
		if(!$this->name)
		{
			$this->name=mb_strtolower($this->first_name.$this->last_name, Yii::$app->charset) ?: explode('@', $this->email)[0];
		}

		if(!in_array($this->gender, [static::GENDER_UNKNOWN, static::GENDER_FEMALE, static::GENDER_MALE]))
		{
			$this->gender=$this->gender=='female' ? static::GENDER_FEMALE : ($this->gender=='male' ? static::GENDER_MALE : static::GENDER_UNKNOWN);
		}

		if(!in_array($this->language, Yii::$app->getI18n()->languages))
		{
			$this->language=Yii::$app->language;
		}

		return parent::beforeValidate();
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * Login after insert.
	 * @var AfterSaveEvent $event
	 */
	public function onAfterInsert($event)
	{
		if(!$this->getIsUnconfirmed() || Yii::$app->params['user.unconfirmedLogin'])
		{
			Yii::$app->getUser()->login($this);
		}
	}
}