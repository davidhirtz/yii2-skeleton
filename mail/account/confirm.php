<?php
/**
 * Resend confirm mail.
 *
 * @var yii\web\View $this
 * @var \yii\mail\MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */
$this->title=Yii::t('app', 'Confirm your account');
?>
<p><?= Yii::t('app', 'Hi {name}, ', ['name'=>$user->getUsername()]); ?></p>
<p><?= Yii::t('app', 'You have recently requested a new account confirmation email. Click the link below to confirm your account.'); ?><br></p>
<p><?php echo Yii::t('app', 'Thank you!'); ?></p>
<div class="btn-wrap">
	<a href="<?= $user->getEmailConfirmationUrl(); ?>" class="btn btn-primary"><?= Yii::t('app', 'Confirm Email'); ?></a>
</div>
