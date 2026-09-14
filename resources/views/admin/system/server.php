<?php

declare(strict_types=1);

/**
 * @see SystemController::actionServer()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\DirectoryAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemSubmenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ServerInfo;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'SYSTEM_SERVER'));

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo SystemSubmenu::make();
echo DirectoryAlert::make();
echo ServerInfo::make();
