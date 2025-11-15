<?php
/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

declare(strict_types=1);

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Create New Redirect'));
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
?>
