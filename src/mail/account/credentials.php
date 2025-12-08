<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var UserForm $form
 */

use Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'Your Account');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $form->user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'Here is your login information for {name}.', ['name' => Yii::$app->name]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'Email'); ?></td>
        <td><?= $form->user->email; ?></td>
    </tr>
    <?php if ($form->newPassword) { ?>
        <tr>
            <td><?= Yii::t('skeleton', 'Password'); ?></td>
            <td><?= $form->newPassword; ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'Please click the link below to login and consider changing your password immediately.'); ?></p>
<p><a href="<?= $form->getLoginUrl(); ?>"><?= $form->getLoginUrl(); ?></a></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>
