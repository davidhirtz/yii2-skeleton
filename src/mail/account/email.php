<?php
/**
 * New email mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var UserForm $user
 * @var string $oldEmail
 */

use davidhirtz\yii2\skeleton\models\forms\UserForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Email confirmation');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'You have recently changed your registered email from {old} to {new}.', [
        'old' => $user->oldEmail,
        'new' => $user->email
    ]); ?>
    <?= Yii::t('skeleton', 'Please click the link below to verify your new email address.'); ?></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
<div class="btn-wrap">
    <a href="<?= $user->getEmailConfirmationUrl(); ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'Confirm Email'); ?></a>
</div>
