<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin;

use Hirtz\Skeleton\modules\admin\config\DashboardPanelConfig;

/**
 * @mixin \Hirtz\Skeleton\base\Module
 */
interface ModuleInterface
{
    /**
     * @return array<string, DashboardPanelConfig>
     */
    public function getDashboardPanels(): array;

    public function getMainMenuItems(): array;
}
