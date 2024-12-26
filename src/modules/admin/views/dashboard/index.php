<?php
declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController::actionIndex()
 * @var View $this
 * @var array $panels
 */

use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ListGroup;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'Admin'));
?>
<h1 class="page-header"><?= Yii::$app->name; ?></h1>

<div class="row justify-center">
    <?php foreach ($panels as $panel) {
        ?>
        <div class="dashboard-card">
            <?= Panel::widget([
                'title' => $panel['name'],
                'content' => ListGroup::widget([
                    'items' => $panel['items'],
                ]),
            ]); ?>
        </div>
        <?php
    }
?>
</div>
