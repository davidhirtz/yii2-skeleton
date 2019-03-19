<?php
/**
 * Edit account form.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\skeleton\models\forms\UserForm $user
 * @var \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use rmrevin\yii\fontawesome\FAB;
use rmrevin\yii\fontawesome\FAR;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Account'));
$this->setBreadcrumb($this->title);
?>
<?php
if ($user->isUnconfirmed()) {
    ?>
    <div class="alert alert-warning">
        <?php
        echo Yii::t('skeleton', 'Your email address "{email}" was not yet confirmed. Please check your inbox or click {here} to request a new confirmation email.', [
            'email' => $user->email,
            'here' => Html::a(Yii::t('skeleton', 'here'), ['resend']),
        ]);
        ?>
    </div>
    <?php
}
?>

<?= Html::errorSummary($user, [
    'header' => Yii::t('skeleton', 'Your account could not be updated'),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $user,
    ]),
]);
?>

<?php Panel::begin([
    'title' => Yii::t('skeleton', 'Clients'),
]) ?>
<?php
if ($user->authClients) {
    ?>
    <table class="table table-vertical table-striped">
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
        foreach ($user->authClients as $auth) {
            $client = $auth->getClientClass();
            $url = $client::getExternalUrl($auth);
            $title = $client->getTitle();
            ?>
            <tr>
                <td><?= $title; ?></td>
                <td><?= $url ? Html::a($auth->getDisplayName(), $url, ['target' => '_blank']) : $auth->getDisplayName(); ?></td>
                <td class="d-none d-table-cell-md"><?= \davidhirtz\yii2\timeago\Timeago::tag($auth->updated_at); ?>
                <td class="d-none d-table-cell-lg"><?= \davidhirtz\yii2\timeago\Timeago::tag($auth->created_at); ?>
                <td class="text-right">
                    <a href="<?= Url::to(['deauthorize', 'id' => $auth->id, 'name' => $auth->name]) ?>" data-method="post" data-confirm="<?= Yii::t('skeleton', 'Are you sure your want to remove your {client} account?', ['client' => $title]); ?>" data-toggle="tooltip" title="<?= Yii::t('skeleton', 'Remove {client}', ['client' => $title]); ?>" class="btn btn-danger">
                        <?= FAR::icon('trash-alt'); ?>
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
            'clientCount' => count($user->authClients),
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
                            <a href="<?= Url::to(['auth', 'client' => 'facebook']); ?>" class="list-group-item">
                                <?= FAB::icon('facebook-f', ['class' => 'fa-fw']); ?>
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
if (!$user->isOwner()) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('skeleton', 'Delete Account'),
        'content' => DeleteActiveForm::widget([
            'model' => $user,
            'attribute' => 'name',
            'action' => ['delete'],
            'message' => Yii::t('skeleton', 'Type your username in the text field below to delete your account, all related items and uploaded files. This cannot be undone, please be certain!'),
        ])
    ]);
} else {
    ?>
    <div class="alert alert-warning">
        <?= Yii::t('skeleton', 'You cannot delete your account, because you are the owner of this website.'); ?>
    </div>
    <?php
}
?>