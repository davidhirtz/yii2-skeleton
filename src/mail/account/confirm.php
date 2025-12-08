<?php
declare(strict_types=1);

/**
 * Resend confirm mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var \Hirtz\Skeleton\Models\User $user
 */

use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Confirm your account');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'You have recently requested a new account confirmation email. Click the link below to confirm your account.'); ?>
    <br></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
<div class="btn-wrap">
    <a href="<?= $user->getEmailConfirmationUrl(); ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'Confirm Email'); ?></a>
</div>
