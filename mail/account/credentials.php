<?php
/**
 * User sign up mail.
 *
 * @var yii\web\View $this
 * @var \yii\mail\MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm $user
 */

use yii\helpers\Url;

$this->title = Yii::t('skeleton', 'Your Account');
?>
<p><?= Yii::t('skeleton', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p><?= Yii::t('skeleton', 'Here is your login information for {name}.', ['name' => Yii::$app->name]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('skeleton', 'Email'); ?></td>
        <td><?= $user->email; ?></td>
    </tr>
    <tr>
        <td><?= Yii::t('skeleton', 'Password'); ?></td>
        <td><?= $user->newPassword; ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('skeleton', 'Please click the link below to login and consider changing your password immediately.'); ?></p>
<p><a href="<?= $url = Url::to(Yii::$app->getUser()->loginUrl, true); ?>"><?= $url; ?></a></p>
<p><?php echo Yii::t('skeleton', 'Thank you!'); ?></p>