<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;

/**
 * @mixin \Hirtz\Skeleton\Base\Module
 */
interface ModuleInterface
{
    /**
     * @return array<string, DashboardPanel>
     */
    public function getDashboardPanels(): array;

    public function getMainMenuItems(): array;
}
