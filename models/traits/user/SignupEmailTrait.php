<?php
namespace davidhirtz\yii2\skeleton\models\traits\user;
use Yii;

/**
 * Class SignupEmailTrait.
 * @package davidhirtz\yii2\skeleton\models\traits\user
 *
 * @property string $email
 */
trait SignupEmailTrait
{
	/**
	 * Sends email confirmation.
	 */
	public function sendSignupEmail()
	{
		$mail=Yii::$app->getMailer()->compose('@skeleton/mail/account/create', [
			'user'=>$this,
		]);

		$mail->setSubject(Yii::t('app', 'Sign up confirmation'))
			->setFrom(Yii::$app->params['app.email'] ?: 'hostmaster@'.$_SERVER['SERVER_NAME'])
			->setTo($this->email)
			->send();
	}
}