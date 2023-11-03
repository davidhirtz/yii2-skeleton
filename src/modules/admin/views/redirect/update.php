<?php
/**
 * @see RedirectController::actionUpdate()
 *
 * @var View $this
 * @var Redirect $redirect
 * @var RedirectActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Update Redirect'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);
?>
<h1 class="page-header">
    <a href="<?= Url::toRoute(['index']) ?>"><?= Yii::t('skeleton', 'Redirects'); ?></a>
</h1>

<?= Html::errorSummary($redirect); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => RedirectActiveForm::widget([
        'model' => $redirect,
    ]),
]); ?>

<?= Panel::widget([
    'title' => Yii::t('skeleton', 'Additional Redirects'),
    'content' => RedirectGridView::widget([
        'redirect' => $redirect,
    ]),
]); ?>

<?= Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('skeleton', 'Delete Redirect'),
    'content' => DeleteActiveForm::widget([
        'model' => $redirect,
    ]),
]); ?>
