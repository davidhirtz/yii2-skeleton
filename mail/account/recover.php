<?php
/**
 * Password recover mail.
 *
 * @var yii\web\View $this
 * @var \yii\mail\MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */
$this->title=Yii::t('app', 'Reset your password');
?>
<p><?= Yii::t('app', 'Hi {name}, ', ['name'=>$user->getUsername()]); ?></p>
<p>
	<?= Yii::t('app', 'You have recently requested to change your password.'); ?>
	<?php echo Yii::t('app', 'If you have not requested a new password, please ignore this message!'); ?>
</p>
<p><?= Yii::t('app', 'Please click the link below to choose a new password.'); ?></p>
<p><?php echo Yii::t('app', 'Thank you!'); ?></p>
<div class="btn-wrap">
	<a href="<?= $user->getPasswordResetUrl(); ?>" class="btn btn-primary"><?= Yii::t('app', 'Reset your password'); ?></a>
</div>
