<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\MigrationAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemSubmenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ApplicationInfo;
use Hirtz\Skeleton\Modules\Admin\Widgets\SentryAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo SystemSubmenu::make();
echo MigrationAlert::make();
echo SentryAlert::make();
echo ApplicationInfo::make();
