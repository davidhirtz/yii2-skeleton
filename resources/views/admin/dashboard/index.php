<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\EnvironmentAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\DashboardHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;

$this->title(Yii::t('skeleton', 'DASHBOARD_NAV_ITEM_DASHBOARD'));

echo DashboardHeader::make();
echo EnvironmentAlert::make();
echo Dashboard::make();
