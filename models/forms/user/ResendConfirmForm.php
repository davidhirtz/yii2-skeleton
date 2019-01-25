<?php
namespace davidhirtz\yii2\skeleton\models\forms\user;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * Class ResendConfirmForm.
 * @package davidhirtz\yii2\skeleton\models\forms\user
 *
 * @property \davidhirtz\yii2\skeleton\models\User $user
 * @see ResendConfirmForm::getUser()
 */
class ResendConfirmForm extends Model
{
	const EMAIL_SPAM_PROTECTION='10 mins';

	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var User
	 * @see ResendConfirmForm::getUser()
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

			elseif(!$user->email_confirmation_code)
			{
				$this->addError('email', Yii::t('app', 'Your account was already confirmed!'));
			}

			elseif($user->updated_at->modify(static::EMAIL_SPAM_PROTECTION)>new DateTime())
			{
				$this->addError('email', Yii::t('app', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', ['email'=>$user->email]));
			}
		}

		parent::afterValidate();
	}

	/**
	 * @return bool
	 */
	public function resend()
	{
		if($this->validate())
		{
			$user=$this->getUser();
			$user->on($user::EVENT_AFTER_UPDATE, [$this, 'sendConfirmEmail']);

			return $user->update(false);
		}
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * Sends email confirm code email.
	 */
	public function sendConfirmEmail()
	{
		$user=$this->getUser();

		$mail=Yii::$app->mailer->compose('@skeleton/mail/account/confirm', [
			'user'=>$user,
		]);

		$mail->setSubject(Yii::t('app', 'Confirm your account'))
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
				->select(['id', 'status', 'name', 'email', 'is_owner', 'email_confirmation_code', 'updated_at'])
				->limit(1)
				->one();
		}

		return $this->_user;
	}

	/**
	 * @param User $user
	 */
	public function setUser($user)
	{
		$this->_user=$user;
		$this->email=$user ? $user->email : null;
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