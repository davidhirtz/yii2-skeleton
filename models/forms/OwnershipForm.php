<?php
namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

/**
 * Class OwnershipForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class OwnershipForm extends Model
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var User
	 * @see OwnerForm::getUser()
	 */
	private $_user;

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
				['name'],
				'required',
			],
			[
				['name'],
				'validateUser',
			],
		];
	}

	/**
	 * @see PasswordResetForm::rules()
	 * @return bool
	 */
	public function validateUser()
	{
		$user=$this->getUser();

		if(!$user)
		{
			$this->addError('name', Yii::t('app', 'The user {name} was not found.', ['name'=>$this->name]));
		}

		elseif($user->isDisabled())
		{
			$this->addError('name', Yii::t('app', 'This user is currently disabled and thus can not be made website owner!'));
		}

		elseif($user->isOwner())
		{
			$this->addError('name', Yii::t('app', 'This user is already the owner of the website!'));
		}

		return !$this->hasErrors();
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Transfers the website ownership to user.
	 */
	public function transfer()
	{
		if($this->validate())
		{
			User::updateAll(['is_owner'=>false, 'updated_at'=>new DateTime], ['is_owner'=>true]);

			$user=$this->getUser();
			$user->is_owner=true;

			return $user->update(false);
		}

		return false;
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return User
	 */
	public function getUser()
	{
		if($this->_user===null)
		{
			$this->_user=User::findByName($this->name)
				->select(['id', 'status', 'name', 'is_owner', 'updated_at'])
				->limit(1)
				->one();
		}

		return $this->_user;
	}

	/***********************************************************************
	 * Model.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name'=>Yii::t('app', 'Username'),
		];
	}
}