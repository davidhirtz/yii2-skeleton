<?php
/**
 * User sign up mail.
 *
 * @var yii\web\View $this
 * @var \yii\mail\MessageInterface $message
 * @var \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm $user
 */

use yii\helpers\Url;

$this->title = Yii::t('app', 'Your Account');
?>
<p><?= Yii::t('app', 'Hi {name}, ', ['name' => $user->getUsername()]); ?></p>
<p><?= Yii::t('app', 'Here is your login information for {name}.', ['name' => Yii::$app->name]); ?></p>
<table>
    <tbody>
    <tr>
        <td><?= Yii::t('app', 'Email'); ?></td>
        <td><?= $user->email; ?></td>
    </tr>
    <tr>
        <td><?= Yii::t('app', 'Password'); ?></td>
        <td><?= $user->newPassword; ?></td>
    </tr>
    </tbody>
</table>
<p><?= Yii::t('app', 'Please click the link below to login and consider changing your password immediately.'); ?></p>
<p><a href="<?= $url = Url::to(Yii::$app->getUser()->loginUrl, true); ?>"><?= $url; ?></a></p>
<p><?php echo Yii::t('app', 'Thank you!'); ?></p>