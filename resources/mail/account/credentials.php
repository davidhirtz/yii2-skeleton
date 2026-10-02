<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var UserForm $form
 */

use Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm;
use yii\helpers\Html;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TITLE');
$passwordResetUrl = $form->getPasswordResetUrl();
?>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_GREETING', ['name' => Html::encode($form->user->getUsername())]); ?></p>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TEXT', ['name' => Html::encode(Yii::$app->name)]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'USER_EMAIL_LABEL'); ?></td>
        <td><?= Html::encode($form->user->email); ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_PASSWORD_RESET_TEXT'); ?></p>
<p><a href="<?= Html::encode($passwordResetUrl); ?>"><?= Html::encode($passwordResetUrl); ?></a></p>
<p><?php echo Yii::t('skeleton', 'MAIL_ACCOUNT_THANK_YOU'); ?></p>
