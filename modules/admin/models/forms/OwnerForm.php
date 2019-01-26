<?php
namespace davidhirtz\yii2\skeleton\modules\admin\models\forms\user;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * Class OwnerForm.
 * @package davidhirtz\yii2\skeleton\modules\admin\models\forms\user
 */
class OwnerForm extends Model
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var User
	 * @see OwnerForm::getUser()
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

		elseif($user->getIsDisabled())
		{
			$this->addError('name', Yii::t('app', 'This user is currently disabled and thus can not be made website owner!'));
		}

		elseif($user->getIsOwner())
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
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return User
	 */
	public function getUser()
	{
		if($this->_user===false)
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