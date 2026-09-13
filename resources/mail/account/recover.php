<?php
declare(strict_types=1);

/**
 * Password recover mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var \Hirtz\Skeleton\Models\User $user
 * @var string $url
 */

use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_RECOVER_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => $user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'MAIL_ACCOUNT_RECOVER_TEXT'); ?>
    <?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_RECOVER_IGNORE'); ?>
</p>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_PASSWORD_RESET_TEXT'); ?></p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
<div class="btn-wrap">
    <a href="<?= $url; ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'MAIL_ACCOUNT_RECOVER_TITLE'); ?></a>
</div>
