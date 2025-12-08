<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\DashboardController::actionIndex()
 * @var View $this
 * @var array $panels
 */

use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\navs\Header;
use Hirtz\Skeleton\widgets\panels\Dashboard;

$this->title(Yii::t('skeleton', 'Admin'));

echo Header::make()
    ->title(Yii::$app->name);

echo Dashboard::make()
    ->panels($panels);
