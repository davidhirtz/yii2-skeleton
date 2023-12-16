<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use Yii;

/**
 * @property string $email
 */
trait SignupEmailTrait
{
    public function sendSignupEmail(): void
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
