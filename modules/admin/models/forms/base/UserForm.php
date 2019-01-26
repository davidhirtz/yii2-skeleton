<?php
namespace davidhirtz\yii2\skeleton\modules\admin\models\forms\base;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;

/**
 * Class BaseUserForm.
 * @package davidhirtz\yii2\skeleton\modules\admin\models\forms\base
 */
class UserForm extends User
{
	/**
	 * @var string
	 */
	public $newPassword;

	/**
	 * @var string
	 */
	public $repeatPassword;

	/**
	 * @var bool
	 */
	public $sendEmail;

	/***********************************************************************
	 * Behaviors.
	 ***********************************************************************/

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array_merge(parent::behaviors(), [
			'BlameableBehavior'=>[
				'class'=>BlameableBehavior::class,
				'attributes'=>[
					static::EVENT_BEFORE_INSERT=>['created_by_user_id'],
				],
			],
		]);
	}

	/***********************************************************************
	 * Validation.
	 ***********************************************************************/

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		$rules=parent::rules();

		if($this->getIsNewRecord())
		{
			$rules[]=[
				['newPassword', 'repeatPassword'],
				'required',
			];
		}

		return array_merge($rules, [
			[
				['newPassword'],
				'filter',
				'filter'=>'trim',
			],
			[
				['newPassword'],
				'string',
				'min'=>Yii::$app->params['user.passwordMinLength'],
				'skipOnEmpty'=>true,
			],
			[
				['repeatPassword'],
				'compare',
				'compareAttribute'=>'newPassword',
				'message'=>Yii::t('app', 'The password must match the new password.'),
			],
			[
				['sendEmail'],
				'boolean',
			],
		]);
	}

	/***********************************************************************
	 * Events.
	 ***********************************************************************/

	/**
	 * @param bool $insert
	 * @return bool
	 */
	public function beforeSave($insert)
	{
		// Emails set by admin are automatically confirmed.
		if($this->getOldAttribute('email')!=$this->email)
		{
			$this->email_confirmation_code=null;
		}

		if($this->newPassword)
		{
			$this->generatePasswordHash($this->newPassword);
		}

		return parent::beforeSave($insert);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		if($insert)
		{
			if($this->sendEmail)
			{
				$this->sendCredentialsEmail();
			}
		}
		else
		{
			// Log user out if unconfirmed logins are not allowed and render all auth cookies invalid on a password change.
			if(isset($changedAttributes['password']))
			{
				$this->deleteAuthKeys();
				$this->deleteActiveSessions();
			}
		}

		parent::afterSave($insert, $changedAttributes);
	}

	/***********************************************************************
	 * Methods.
	 ***********************************************************************/

	/**
	 * Sends credentials via email.
	 */
	public function sendCredentialsEmail()
	{
		Yii::$app->language=$this->language;

		$mail=Yii::$app->getMailer()->compose('@app/mail/account/credentials', [
			'user'=>$this,
		]);

		$mail->setSubject(Yii::t('app', 'Your {name} Account', ['name'=>Yii::$app->name]))
			->setFrom(Yii::$app->params['app.email'] ?: 'hostmaster@'.Yii::$app->getRequest()->getHostName())
			->setTo($this->email)
			->send();

		Yii::$app->language=Yii::$app->getUser()->getIdentity()->language;
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
			'newPassword'=>$this->getIsNewRecord() ? Yii::t('app', 'Password') : Yii::t('app', 'New password'),
			'repeatPassword'=>Yii::t('app', 'Repeat password'),
			'sendEmail'=>Yii::t('app', 'Send user account details via email'),
		]);
	}
}