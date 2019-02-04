<?php
namespace davidhirtz\yii2\skeleton\models\forms\base;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * Class UserForm.
 * @package davidhirtz\yii2\skeleton\models\forms\base
 *
 * @property \davidhirtz\yii2\skeleton\models\forms\base\UserForm $model
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
	public $oldPassword;

	/**
	 * @var string
	 */
	public $oldEmail;

	/**
	 * @inheritdoc
	 */
	public function rules(): array
	{
		return array_merge(parent::rules(), [
			[
				['newPassword', 'oldPassword'],
				'filter',
				'filter'=>'trim',
			],
			[
				['newPassword', 'oldPassword'],
				'string',
				'min'=>$this->passwordMinLength,
			],
			[
				['newPassword'],
				'validateNewPassword',
				'skipOnError'=>true,
			],
			[
				['timezone'],
				'required',
			],
		]);
	}

	/**
	 * @return bool
	 */
	protected function validateNewPassword(): bool
	{
		return !$this->validatePassword($this->oldPassword) ? $this->addInvalidAttributeError('oldPassword') : true;
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		$this->oldEmail=$this->email;
		parent::afterFind();
	}

	/**
	 * @param bool $insert
	 * @return bool
	 */
	public function beforeSave($insert): bool
	{
		if($this->isAttributeChanged('email'))
		{
			$this->generateEmailConfirmationCode();
		}

		return parent::beforeSave($insert);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		if(!$insert)
		{
			if(array_key_exists('email', $changedAttributes))
			{
				if(!Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled())
				{
					Yii::$app->getUser()->logout(false);
					Yii::$app->getSession()->addFlash('success', Yii::t('app', 'Please check your emails to confirm your new email address!'));
				}

				$this->sendEmailConfirmationEmail();
			}

			if(isset($changedAttributes['password']))
			{
				$this->deleteAuthKeys();
				$this->deleteActiveSessions(Yii::$app->getSession()->getId());
			}
		}

		parent::afterSave($insert, $changedAttributes);
	}

	/**
	 * Sends email confirmation mail.
	 */
	public function sendEmailConfirmationEmail()
	{
		Yii::$app->getMailer()->compose('@skeleton/mail/account/email', ['user'=>$this])
			->setSubject(Yii::t('app', 'Please confirm your new email address'))
			->setFrom(Yii::$app->params['email'])
			->setTo($this->email)
			->send();
	}

	/**
	 * @return array
	 */
	public function scenarios()
	{
		return [
			static::SCENARIO_DEFAULT=>[
                'city',
                'country',
                'email',
                'first_name',
                'language',
                'last_name',
                'name',
                'newPassword',
                'oldPassword',
                'timezone',
            ],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
			'newPassword'=>Yii::t('app', 'New password'),
			'oldPassword'=>Yii::t('app', 'Current password'),
			'upload'=>Yii::t('app', 'Picture'),
		]);
	}
}