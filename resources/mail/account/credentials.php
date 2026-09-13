<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var UserForm $form
 */

use Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Your Account');
$passwordResetUrl = $form->getPasswordResetUrl();
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $form->user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'Here is your login information for {name}.', ['name' => Yii::$app->name]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'Email'); ?></td>
        <td><?= $form->user->email; ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'Please click the link below to choose a new password.'); ?></p>
<p><a href="<?= $passwordResetUrl; ?>"><?= $passwordResetUrl; ?></a></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
