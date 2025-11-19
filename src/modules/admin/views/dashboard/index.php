<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController::actionIndex()
 * @var View $this
 * @var array $panels
 */

use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\navs\Header;
use davidhirtz\yii2\skeleton\widgets\panels\Dashboard;

$this->title(Yii::t('skeleton', 'Admin'));

echo Header::make()
    ->title(Yii::$app->name);

echo Dashboard::make()
    ->panels($panels);
