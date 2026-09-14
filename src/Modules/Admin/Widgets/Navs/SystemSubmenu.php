<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Navs\Submenu;
use Override;
use Yii;

class SystemSubmenu extends Submenu
{
    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            application: $this->getApplicationItem(),
            server: $this->getServerItem(),
            maintenance: $this->getMaintenanceItem(),
        );

        parent::configure();
    }

    /**
     * @see SystemController::actionIndex()
     */
    protected function getApplicationItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'SYSTEM_APPLICATION'))
            ->url(['/admin/system/index'])
            ->icon('circle-info');
    }

    /**
     * @see SystemController::actionServer()
     */
    protected function getServerItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'SYSTEM_SERVER'))
            ->url(['/admin/system/server'])
            ->icon('server');
    }

    /**
     * @see SystemController::actionMaintenance()
     */
    protected function getMaintenanceItem(): NavItem
    {
        return NavItem::make()
            ->label(Yii::t('skeleton', 'SYSTEM_MAINTENANCE'))
            ->url(['/admin/system/maintenance'])
            ->icon('screwdriver-wrench');
    }
}
