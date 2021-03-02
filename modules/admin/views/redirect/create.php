<?php
/**
 * Create redirect form.
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Create New Redirect'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);
?>
    <h1 class="page-header">
        <a href="<?= Url::toRoute(['index']) ?>"><?= Yii::t('skeleton', 'Redirects'); ?></a>
    </h1>

<?= Html::errorSummary($redirect); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => $redirect->getActiveForm()::widget([
        'model' => $redirect,
    ]),
]);
?>