<?php
declare(strict_types=1);

/**
 * Resend confirm mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var \Hirtz\Skeleton\Models\User $user
 * @var string $url
 */

use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => $user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_TEXT'); ?>
    <br></p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
<div class="btn-wrap">
    <a href="<?= $url; ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CONFIRM_EMAIL_BUTTON'); ?></a>
</div>
