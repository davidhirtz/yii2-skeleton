<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var MessageInterface $message
 * @var User $user
 * @var string $url
 */

use Hirtz\Skeleton\Models\User;
use yii\helpers\Html;
use yii\mail\MessageInterface;

$this->title = Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TITLE');
?>
<p><?= Yii::t('skeleton', 'MAIL_GREETING_TEXT', ['name' => Html::encode($user->getUsername())]); ?></p>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_CREDENTIALS_TEXT', ['name' => Html::encode(Yii::$app->name)]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'USER_EMAIL_LABEL'); ?></td>
        <td><?= Html::encode($user->email); ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'MAIL_ACCOUNT_PASSWORD_RESET_TEXT'); ?></p>
<p><a href="<?= Html::encode($url); ?>"><?= Html::encode($url); ?></a></p>
<p><?php echo Yii::t('skeleton', 'MAIL_THANK_YOU_TEXT'); ?></p>
