<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Yii;

trait SignupEmailTrait
{
    public function sendSignupEmail(): bool
    {
        $mail = Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/create', [
            'user' => $this->user,
            'url' => $this->user->createEmailConfirmationUrl(),
        ]);

        return $mail->setSubject(Yii::t('skeleton', 'SIGNUP_EMAIL_SIGN_UP_CONFIRMATION'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }
}
