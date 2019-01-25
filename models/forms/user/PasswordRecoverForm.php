<?php
namespace davidhirtz\yii2\skeleton\models\forms\user;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * Class PasswordRecoverForm.
 * @package davidhirtz\yii2\skeleton\models\forms\user
 *
 * @property User $user
 * @see PasswordRecoverForm::getUser()
 */
class PasswordRecoverForm extends Model
{
	const EMAIL_SPAM_PROTECTION='10 mins';

	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var User
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
				['email'],
				'filter',
				'filter'=>'trim',
			],
			[
				['email'],
				'required',
			],
			[
				['email'],
				'email',
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

			if(!$user)
			{
				$this->addError('email', Yii::t('app', 'Your email was not found.'));
			}

			elseif($user->getIsDisabled() && !$user->getIsOwner())
			{
				$this->addError('email', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
			}

			elseif($user->password_reset_code && $user->updated_at->modify(static::EMAIL_SPAM_PROTECTION)>new DateTime())
			{
				$this->addError('email', Yii::t('app', 'We have just sent a link to reset your password to {email}. Please check your inbox!', ['email'=>$user->email]));
			}
		}

		parent::afterValidate();
	}

	/**
	 * @return bool
	 */
	public function recover()
	{
		if($this->validate())
		{
			$user=$this->getUser();

			if(!$user->password_reset_code)
			{
				$user->generatePasswordResetCode();
			}

			$user->on($user::EVENT_AFTER_UPDATE, [$this, 'sendPasswordResetEmail']);
			return $user->update(false);
		}
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * Sends password reset code email.
	 */
	public function sendPasswordResetEmail()
	{
		$user=$this->getUser();

		$mail=Yii::$app->mailer->compose('@skeleton/mail/account/recover', [
			'user'=>$user,
		]);

		$mail->setSubject(Yii::t('app', 'Reset your password'))
			->setFrom(Yii::$app->params['app.email'] ?: 'hostmaster@'.$_SERVER['SERVER_NAME'])
			->setTo($user->email)
			->send();
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
			$this->_user=User::findByEmail($this->email)
				->select(['id', 'status', 'name', 'email', 'is_owner', 'password_reset_code', 'updated_at'])
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
			'email'=>Yii::t('app', 'Email'),
		];
	}
}