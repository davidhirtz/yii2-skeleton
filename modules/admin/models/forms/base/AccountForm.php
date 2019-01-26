<?php
namespace davidhirtz\yii2\skeleton\modules\admin\models\forms\base;
use Yii;

/**
 * Class AccountForm.
 * @package davidhirtz\yii2\skeleton\modules\admin\models\forms\base
 */
class AccountForm extends \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm
{
	/**
	 * @var string
	 */
	public $oldPassword;

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return array_merge(parent::rules(), [
			[
				['name'],
				'validateName',
				'skipOnError'=>true,
			],
			[
				['email'],
				'validateEmail',
				'skipOnError'=>true,
			],
			[
				['newPassword', 'oldPassword'],
				'filter',
				'filter'=>'trim',
			],
			[
				['newPassword', 'oldPassword'],
				'string',
				'min'=>Yii::$app->params['user.passwordMinLength'],
			],
			[
				['newPassword'],
				'validateNewPassword',
				'skipOnError'=>true,
			],
		]);
	}

	/**
	 * Validates old password if username was changed.
	 * @see AccountForm::rules()
	 */
	public function validateName()
	{
		if($this->name!=$this->getOldAttribute('name'))
		{
			$this->validateOldPassword(Yii::t('app', 'username'));
		}
	}

	/**
	 * Validates old password if email was changed.
	 * @see AccountForm::rules()
	 */
	public function validateEmail()
	{
		if($this->email!=$this->getOldAttribute('email') && $this->validateOldPassword(Yii::t('app', 'email')))
		{
			$this->generateEmailConfirmationCode();
		}
	}

	/**
	 * Sets password and validates old password if new password was changed.
	 * @see AccountForm::rules()
	 */
	public function validateNewPassword()
	{
		if($this->newPassword && $this->validateOldPassword(Yii::t('app', 'password')))
		{
			$this->generatePasswordHash($this->newPassword);
		}
	}

	/**
	 * Validates old password.
	 *
	 * Make sure password is set, this might not be the case
	 * if the user was create by an external source.
	 *
	 * @param string $label
	 * @return bool
	 */
	private function validateOldPassword($label)
	{
		if($this->password && !$this->validatePassword($this->oldPassword))
		{
			$this->addError('oldPassword', $this->oldPassword ? Yii::t('app', 'Your password is not correct!') : Yii::t('app', 'You need to enter your current password to change your {attribute}.', [
				'attribute'=>$label,
			]));

			return false;
		}

		return true;
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * Set default values.
	 */
	public function afterFind()
	{
		if(!$this->timezone)
		{
			$this->timezone=Yii::$app->timeZone;
		}

		parent::afterFind();
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		/**
		 * Send confirmation email and log user out if unconfirmed
		 * logins are not allowed.
		 */
		if(isset($changedAttributes['email']))
		{
			if(!Yii::$app->params['user.unconfirmedLogin'])
			{
				Yii::$app->getUser()->logout(false);
				Yii::$app->getSession()->addFlash('success', Yii::t('app', 'Please check your emails to confirm your new email address!'));
			}

			$this->sendEmailConfirmationEmail($changedAttributes['email']);
		}

		/**
		 * Set new app language for flash message.
		 */
		if(isset($changedAttributes['language']))
		{
			Yii::$app->language=$this->language;
		}

		parent::afterSave($insert, $changedAttributes);
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Sends email confirmation..
	 * @var string $oldEmail
	 */
	public function sendEmailConfirmationEmail($oldEmail)
	{
		$mail=Yii::$app->getMailer()->compose('@app/mail/account/email', [
			'user'=>$this,
			'oldEmail'=>$oldEmail,
		]);

		$mail->setSubject(Yii::t('app', 'Please confirm your new email address'))
			->setFrom(Yii::$app->params['app.email'] ?: 'hostmaster@'.$_SERVER['SERVER_NAME'])
			->setTo($this->email)
			->send();
	}

	/***********************************************************************
	 * Active Record.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'oldPassword'=>Yii::t('app', 'Password'),
		]);
	}
}