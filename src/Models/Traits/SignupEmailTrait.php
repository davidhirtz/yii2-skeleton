<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Yii;

trait SignupEmailTrait
{
    public function sendSignupEmail(): void
    {
        $mail = Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/create', [
            'user' => $this->user,
        ]);

        $mail->setSubject(Yii::t('skeleton', 'Sign up confirmation'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }
}
