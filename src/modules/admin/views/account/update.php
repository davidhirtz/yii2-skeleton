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
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\timeago\Timeago;
use yii\helpers\Url;

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

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your account could not be updated'),
]); ?>

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
    Panel::begin([
        'title' => Yii::t('skeleton', 'Clients'),
    ]);

    if ($form->user->authClients) {
        ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th><?= Yii::t('skeleton', 'Client'); ?></th>
                <th><?= Yii::t('skeleton', 'Name'); ?></th>
                <th class="d-none d-table-cell-md"><?= Yii::t('skeleton', 'Updated'); ?></th>
                <th class="d-none d-table-cell-lg"><?= Yii::t('skeleton', 'Created'); ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($form->user->authClients as $auth) {
                $client = $auth->getClientClass();
                $url = $client::getExternalUrl($auth);
                $title = $client->getTitle();
                ?>
                <tr>
                    <td><?= $title; ?></td>
                    <td><?= $url ? Html::a($auth->getDisplayName(), $url, ['target' => '_blank']) : $auth->getDisplayName(); ?></td>
                    <td class="d-none d-table-cell-md"><?= Timeago::tag($auth->updated_at); ?>
                    <td class="d-none d-table-cell-lg"><?= Timeago::tag($auth->created_at); ?>
                    <td class="text-right">
                        <a href="<?= Url::to(['deauthorize', 'id' => $auth->id, 'name' => $auth->name]) ?>"
                           data-method="post"
                           data-confirm="<?= Yii::t('skeleton', 'Are you sure your want to remove your {client} account?', ['client' => $title]); ?>"
                           data-toggle="tooltip"
                           title="<?= Yii::t('skeleton', 'Remove {client}', ['client' => $title]); ?>"
                           class="btn btn-danger">
                            <?= Icon::tag('trash-alt'); ?>
                        </a>
                    </td>
                </tr>
                <?php
            }
        ?>
            </tbody>
        </table>
        <hr>
        <?php
    }
    ?>
    <p>
        <?= Yii::t('skeleton', 'Click {here} to add {clientCount, plural, =0{an external client} other{additional clients}} to your account.', [
            'clientCount' => count($form->user->authClients),
            'here' => Html::a(Yii::t('skeleton', 'here'), '#', [
                'data-toggle' => 'modal',
                'data-target' => '#auth-client-modal'
            ]),
        ]); ?>
    </p>
    <?php Panel::end(); ?>
    <div class="modal fade" id="auth-client-modal" tabindex="-1" role="dialog" aria-labelledby="resize-modal-label">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        <?= Yii::t('skeleton', 'Clients'); ?>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="<?= Yii::t('skeleton', 'Close'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php
                        if ((new LoginForm())->isFacebookLoginEnabled()) {
                            ?>
                            <a href="<?= Url::to(['auth', 'authclient' => 'facebook']); ?>"
                               class="list-group-item list-group-item-action">
                                <?= Icon::brand('facebook-f', ['class' => 'fa-fw']); ?>
                                <?= Yii::t('skeleton', 'Login with Facebook'); ?>
                            </a>
                            <?php
                        }
    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
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
