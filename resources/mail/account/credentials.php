<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var UserForm $form
 */

use Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TITLE');
$passwordResetUrl = $form->getPasswordResetUrl();
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => $form->user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TEXT', ['name' => Yii::$app->name]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'USER_EMAIL_LABEL'); ?></td>
        <td><?= $form->user->email; ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_PASSWORD_RESET_TEXT'); ?></p>
<p><a href="<?= $passwordResetUrl; ?>"><?= $passwordResetUrl; ?></a></p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
