<?php
namespace app\models\forms\user;
use davidhirtz\yii2\skeleton\db\Identity;
use app\models\User;
use Yii;
use yii\base\Model;

/**
 * Class ConfirmForm.
 * @package app\models\forms\user
 *
 * @property Identity $user
 * @see LoginForm::getUser()
 */
class ConfirmForm extends Model
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $code;

	/**
	 * @var Identity
	 * @see getUser()
	 */
	private $_user=false;

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[
				['name'],
				'filter',
				'filter'=>'trim',
			],
			[
				['name', 'code'],
				'required',
			],
			[
				['code'],
				'string',
				'length'=>User::EMAIL_CONFIRMATION_CODE_LENGTH,
				'notEqual'=>Yii::t('yii', '{attribute} is invalid.'),
				'skipOnError'=>true,
			],
		];
	}

	/**
	 * Validates user credentials.
	 */
	public function afterValidate()
	{
		if(!$this->hasErrors())
		{
			$user=$this->getUser();

			if(!$user || $user->email_confirmation_code!=$this->code)
			{
				$this->addError('code', Yii::t('yii', '{attribute} is invalid.', [
					'attribute'=>$this->getAttributeLabel('code'),
				]));
			}
		}

		parent::afterValidate();
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Logs in a user using the provided email and password.
	 * @return boolean
	 */
	public function update()
	{
		if($this->validate())
		{
			$user=$this->getUser();
			$user->email_confirmation_code=null;

			return $user->update(false);
		}

		return false;
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return Identity
	 */
	public function getUser()
	{
		if($this->_user===false)
		{
			$this->_user=Identity::findByName($this->name)
				->select(['id', 'status', 'name', 'email_confirmation_code', 'login_count', 'last_login', 'updated_at'])
				->limit(1)
				->one();
		}

		return $this->_user;
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return [
			'name'=>Yii::t('app', 'Username'),
			'code'=>Yii::t('app', 'Email confirmation code'),
		];
	}
}