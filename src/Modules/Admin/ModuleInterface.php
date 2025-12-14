<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Config\DashboardPanelConfig;

/**
 * @mixin \Hirtz\Skeleton\Base\Module
 */
interface ModuleInterface
{
    /**
     * @return array<string, DashboardPanelConfig>
     */
    public function getDashboardPanels(): array;

    public function getMainMenuItems(): array;
}
