<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController::actionIndex()
 * @var View $this
 * @var array $panels
 */

use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;

$this->title(Yii::t('skeleton', 'Admin'));

echo Header::make()
    ->title(Yii::$app->name);

echo Dashboard::make()
    ->panels($panels);
