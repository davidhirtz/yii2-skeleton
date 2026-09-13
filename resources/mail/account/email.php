<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var AccountUpdateForm $form
 * @var string $url
 */

use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => $form->user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_TEXT', [
            'old' => $form->email,
            'new' => $form->user->email
    ]); ?>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_EMAIL_VERIFY_TEXT'); ?></p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
<div class="btn-wrap">
    <a href="<?= $url; ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_EMAIL_BUTTON'); ?></a>
</div>
