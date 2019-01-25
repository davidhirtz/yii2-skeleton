<?php
namespace davidhirtz\yii2\skeleton\models\forms\user;
use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

/**
 * Class PasswordResetForm.
 * @package davidhirtz\yii2\skeleton\models\forms\user
 *
 * @property Identity $user
 * @see PasswordResetForm::getUser()
 */
class PasswordResetForm extends Model
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
	 * @var string
	 */
	public $newPassword;

	/**
	 * @var string
	 */
	public $repeatPassword;

	/**
	 * @var Identity
	 * @see PasswordRecoverForm::getUser()
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
				['name', 'code', 'newPassword', 'repeatPassword'],
				'filter',
				'filter'=>'trim',
			],
			[
				['name'],
				'validateUser',
			],
			[
				['code'],
				'string',
				'length'=>User::PASSWORD_RESET_CODE_LENGTH,
			],
			[
				['newPassword', 'repeatPassword'],
				'required',
			],
			[
				['newPassword'],
				'string',
				'min'=>Yii::$app->params['user.passwordMinLength'],
			],
			[
				['repeatPassword'],
				'compare',
				'compareAttribute'=>'newPassword',
				'message'=>Yii::t('app', 'The password must match the new password.'),
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

		if(!$user || $user->password_reset_code!=$this->code)
		{
			$this->addError('id', Yii::t('app', 'The password recovery url is invalid.'));
		}

		if($user->getIsDisabled() && !$user->getIsOwner())
		{
			$this->addError('id', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
		}

		return !$this->hasErrors();
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Hashes new password and logs in user if possible.
	 *
	 * This method also deletes all cookie auth keys for this user,
	 * so auto login cookies are not working anymore.
	 */
	public function reset()
	{
		if($this->validate())
		{
			$user=$this->getUser();

			$user->generatePasswordHash($this->newPassword);
			$user->password_reset_code=null;

			if(Yii::$app->user->isGuest)
			{
				if(!$user->getIsUnconfirmed() || Yii::$app->params['user.unconfirmedLogin'])
				{
					$user->loginType=UserLogin::TYPE_RESET_PASSWORD;

					$user->deleteAuthKeys();
					$user->deleteActiveSessions();

					/**
					 * Login also takes care of updating the user record.
					 * @see \davidhirtz\yii2\skeleton\db\Identity::afterLogin()
					 */
					return Yii::$app->user->login($user);
				}
			}

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
				->select(['id', 'status', 'name', 'email', 'is_owner', 'email_confirmation_code', 'password_reset_code', 'login_count', 'is_owner', 'last_login', 'updated_at'])
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
		$user=$this->getUser();

		return [
			'newPassword'=>$user && $user->login_count ? Yii::t('app', 'New password') : Yii::t('app', 'Password'),
			'repeatPassword'=>Yii::t('app', 'Repeat password'),
		];
	}
}