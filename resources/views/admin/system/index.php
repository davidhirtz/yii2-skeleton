<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\EnvironmentAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\MigrationAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ApplicationInfo;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ExtensionVersions;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\MaintenanceInfo;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Panels\Panel;

$this->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo MigrationAlert::make();
echo EnvironmentAlert::make();

echo ApplicationInfo::make();
echo ExtensionVersions::make();
echo MaintenanceInfo::make();

/** @see SystemController::actionPhpInfo() */
echo Panel::make()
    ->buttons(Button::make()
        ->primary()
        ->icon('info-circle')
        ->text(Yii::t('skeleton', 'SYSTEM_PHP_INFO'))
        ->url(['php-info'])
        ->target('_blank')
        ->attribute('hx-boost', 'false'));
