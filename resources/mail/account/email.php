<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var AccountCredentialsForm $form
 * @var string $url
 */

use Hirtz\Skeleton\Models\Forms\AccountCredentialsForm;
use yii\helpers\Html;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_GREETING_TEXT', ['name' => Html::encode($form->user->getUsername())]); ?></p>
<p>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_TEXT', [
            'old' => Html::encode($form->email),
            'new' => Html::encode($form->user->email)
    ]); ?>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_VERIFY_TEXT'); ?></p>
<p><?php echo Yii::t('skeleton', 'MAIL_THANK_YOU_TEXT'); ?></p>
<div class="btn-wrap">
    <a href="<?= Html::encode($url); ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_EMAIL_BUTTON'); ?></a>
</div>
