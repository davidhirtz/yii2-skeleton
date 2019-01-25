<?php
/**
 * User sign up mail.
 *
 * @var yii\web\View $this
 * @var \yii\mail\MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\models\forms\user\SignupForm $user
 */
$this->title=Yii::t('app', 'Sign up confirmation');
?>
<p><?= Yii::t('app', 'Hi {name}, ', ['name'=>$user->getUsername()]); ?></p>
<p>
	<?= Yii::t('app', 'Thank you for signing up! Please confirm your email address by clicking the link below.'); ?><br>
</p>
<p><?php echo Yii::t('app', 'Thank you!'); ?></p>
<div class="btn-wrap">
	<a href="<?= $user->getEmailConfirmationUrl(); ?>" class="btn btn-primary"><?= Yii::t('app', 'Confirm Email'); ?></a>
</div>
