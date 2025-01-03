<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */

declare(strict_types=1);

use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Redirects'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);
?>
    <h1 class="page-header">
        <a href="<?= Url::toRoute(['index']) ?>"><?= $this->title; ?></a>
    </h1>
<?= Panel::widget([
    'content' => RedirectGridView::widget([
        'dataProvider' => $provider,
    ]),
]);
