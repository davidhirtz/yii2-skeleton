<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use Yii;

trait SignupEmailTrait
{
    public function sendSignupEmail(): void
    {
        $mail = Yii::$app->getMailer()->compose('@skeleton/mail/account/create', [
            'user' => $this->user,
        ]);

        $mail->setSubject(Yii::t('skeleton', 'Sign up confirmation'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->email)
            ->send();
    }
}
