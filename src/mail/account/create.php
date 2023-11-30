<?php
/**
 * User sign up mail.
 *
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var SignupForm $user
 */

use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Sign up confirmation');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p>
    <?= Yii::t('skeleton', 'Thank you for signing up! Please confirm your email address by clicking the link below.'); ?><br>
</p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
<div class="btn-wrap">
    <a href="<?= $user->getEmailConfirmationUrl(); ?>"
       class="btn btn-primary"><?= Yii::t('skeleton', 'Confirm Email'); ?></a>
</div>
