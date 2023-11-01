<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use Yii;

/**
 * Class SignupEmailTrait
 * @package davidhirtz\yii2\skeleton\models\traits
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
        $mail = Yii::$app->getMailer()->compose('@skeleton/mail/account/create', [
            'user' => $this,
        ]);

        $mail->setSubject(Yii::t('skeleton', 'Sign up confirmation'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }
}