<?php
declare(strict_types=1);

/**
 * Password recover mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */

use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Reset your password');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'You have recently requested to change your password.'); ?>
    <?php echo Yii::t('skeleton', 'If you have not requested a new password, please ignore this message!'); ?>
</p>
<p><?= Yii::t('skeleton', 'Please click the link below to choose a new password.'); ?></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
<div class="btn-wrap">
    <a href="<?= $user->getPasswordResetUrl(); ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'Reset your password'); ?></a>
</div>
