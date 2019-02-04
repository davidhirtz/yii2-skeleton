<?php
namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\models\traits\IdentityTrait;
use davidhirtz\yii2\datetime\DateTime;
use Yii;
use yii\base\Model;

/**
 * Class AccountResendConfirmForm.
 * @package davidhirtz\yii2\skeleton\models\forms
 */
class AccountResendConfirmForm extends Model
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

			elseif($user->isDisabled() && !$user->isOwner())
			{
				$this->addError('email', Yii::t('app', 'Your account is currently disabled. Please contact an administrator!'));
			}

			elseif(!$user->email_confirmation_code)
			{
				$this->addError('email', Yii::t('app', 'Your account was already confirmed!'));
			}

			elseif($this->isAlreadySent())
			{
				$this->addError('email', Yii::t('app', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', ['email'=>$user->email]));
			}
		}

		parent::afterValidate();
	}

	/**
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 * @return bool
	 */
	public function resend()
	{
		if($this->validate())
		{
			$this->getUser()->update(false);
			$this->sendConfirmEmail();

			return true;
		}

		return false;
	}

	/**
	 * Sends email confirm code email.
	 */
	public function sendConfirmEmail()
	{
		$user=$this->getUser();

		Yii::$app->getMailer()->compose('@skeleton/mail/account/confirm', ['user'=>$user])
			->setSubject(Yii::t('app', 'Confirm your account'))
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
		return $this->getUser()->updated_at->modify($this->timeoutSpamProtection)>new DateTime();
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