<?php
namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
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
	use IdentityTrait;

	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var string
	 */
	public $timeoutSpamProtection='10 mins';

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

			elseif($user->isDisabled() && !$user->isOwner())
			{
				$this->addError('email', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
			}

			elseif($this->isAlreadySent())
			{
				$this->addError('email', Yii::t('app', 'We have just sent a link to reset your password to {email}. Please check your inbox!', ['email'=>$user->email]));
			}
		}

		parent::afterValidate();
	}

	/**
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 * @return bool
	 */
	public function recover()
	{
		if($this->validate())
		{
			$user=$this->getUser();
			$user->generatePasswordResetCode();
			$user->update(false);

			$this->sendPasswordResetEmail();
			return true;
		}

		return false;
	}

	/**
	 * Sends password reset code email.
	 */
	public function sendPasswordResetEmail()
	{
		$user=$this->getUser();

		Yii::$app->getMailer()->compose('@skeleton/mail/account/recover', ['user'=>$user])
			->setSubject(Yii::t('app', 'Reset your password'))
			->setFrom(Yii::$app->params['email'])
			->setTo($user->email)
			->send();
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function isAlreadySent()
	{
		$user=$this->getUser();
		return $user->password_reset_code && $user->updated_at->modify($this->timeoutSpamProtection)>new DateTime();
	}

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