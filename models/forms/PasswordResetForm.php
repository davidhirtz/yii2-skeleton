<?php
namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\Identity;
use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

/**
 * Class PasswordResetForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 *
 * @property Identity $user
 * @see PasswordResetForm::getUser()
 */
class PasswordResetForm extends Model
{
	use IdentityTrait;

	/**
	 * @var string
	 */
	public $email;

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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[
				['email', 'code', 'newPassword', 'repeatPassword'],
				'filter',
				'filter'=>'trim',
			],
			[
				['email'],
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
				'min'=>$this->getUser()->passwordMinLength,
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

		if($user->isDisabled() && !$user->isOwner())
		{
			$this->addError('id', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
		}

		return !$this->hasErrors();
	}


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

			if(Yii::$app->getUser()->getIsGuest())
			{
				if(!$user->isUnconfirmed() || Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled())
				{
					$user->loginType=UserLogin::TYPE_RESET_PASSWORD;

					$user->deleteAuthKeys();
					$user->deleteActiveSessions();

					// Login also takes care of updating the user record.
					return Yii::$app->getUser()->login($user);
				}
			}

			return $user->update(false);
		}

		return false;
	}

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