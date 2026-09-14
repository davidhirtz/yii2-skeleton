<?php

declare(strict_types=1);

/**
 * @see SystemController::actionMaintenance()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemSubmenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\MaintenanceInfo;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'SYSTEM_MAINTENANCE'));

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo SystemSubmenu::make();
echo MaintenanceInfo::make();
