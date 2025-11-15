<?php
/**
 * @see RedirectController::actionUpdate()
 *
 * @var View $this
 * @var Redirect $redirect
 * @var RedirectActiveDataProvider $provider
 */

declare(strict_types=1);

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Update Redirect'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);
?>
<h1 class="page-header">
    <a href="<?= Url::toRoute(['index']) ?>"><?= Yii::t('skeleton', 'Redirects'); ?></a>
</h1>

<?php
echo ErrorSummary::make()->models($redirect);

echo Panel::widget([
    'title' => $this->title,
    'content' => RedirectActiveForm::widget([
        'model' => $redirect,
    ]),
]);

echo Panel::widget([
    'title' => Yii::t('skeleton', 'Additional Redirects'),
    'content' => RedirectGridView::widget([
        'redirect' => $redirect,
    ]),
]);

echo Panel::widget([
    'type' => 'danger',
    'title' => Yii::t('skeleton', 'Delete Redirect'),
    'content' => DeleteActiveForm::widget([
        'model' => $redirect,
    ]),
]);
