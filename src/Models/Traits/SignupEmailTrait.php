<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\I18n\Lang;
use Yii;

trait SignupEmailTrait
{
    public function sendSignupEmail(): void
    {
        $mail = Yii::$app->getMailer()->compose('@skeleton/../resources/mail/account/create', [
            'user' => $this->user,
        ]);

        $mail->setSubject(Lang::t('skeleton', 'SIGNUP_EMAIL_SIGN_UP_CONFIRMATION'))
            ->setFrom(Yii::$app->params['email'])
            ->setTo($this->user->email)
            ->send();
    }
}
