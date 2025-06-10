<?php

declare(strict_types=1);

/**
 * @see AccountController::actionUpdate()
 * @see AccountController::actionDeauthorize()
 *
 * @var View $this
 * @var AccountUpdateForm $form
 */

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\AccountUpdateForm;
use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\ErrorSummary;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AuthClientsGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;

$this->setTitle(Yii::t('skeleton', 'Account'));
?>
<h1 class="page-header"><?= $form->user->getUsername(); ?></h1>
<?php
if ($form->user->isUnconfirmed()) {
    ?>
    <div class="alert alert-warning">
        <?php
        echo Yii::t('skeleton', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
            'email' => $form->email,
            'here' => Html::a(Yii::t('skeleton', 'here'), ['resend']),
        ]);
    ?>
    </div>
    <?php
}
?>

<?php
echo ErrorSummary::make()
    ->models($form)
    ->title(Yii::t('skeleton', 'Your account could not be updated'))
    ->render();

?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => AccountActiveForm::widget([
        'model' => $form,
    ]),
]);
?>

<?php if (Yii::$app->getUser()->enableGoogleAuthenticator) {
    echo Panel::widget([
        'title' => Yii::t('skeleton', 'Google Authenticator'),
        'content' => GoogleAuthenticatorActiveForm::widget([
            'model' => GoogleAuthenticatorForm::create([
                'user' => $form->user,
            ]),
        ]),
    ]);
}
?>

<?php if (Yii::$app->getAuthClientCollection()->clients) {
    echo Panel::widget([
        'title' => Yii::t('skeleton', 'Clients'),
        'content' => AuthClientsGridView::widget([
            'user' => $form->user,
        ]),
    ]);
} ?>

<?php if ($form->user->isDeletable()) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('skeleton', 'Delete Account'),
        'content' => DeleteActiveForm::widget([
            'model' => $form->user,
            'attribute' => 'password',
            'action' => ['delete'],
            'message' => Yii::t('skeleton', 'Type your password in the text field below to delete your account, all related items and uploaded files. This cannot be undone, please be certain!'),
            'fieldOptions' => [
                'inputOptions' => [
                    'type' => 'password',
                ],
            ],
        ])
    ]);
} elseif ($form->user->isOwner()) {
    echo Html::warning(Yii::t('skeleton', 'You cannot delete your account, because you are the owner of this website.'));
}
?>
