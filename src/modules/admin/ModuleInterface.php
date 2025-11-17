<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin;

use davidhirtz\yii2\skeleton\modules\admin\config\DashboardPanelConfig;

/**
 * @mixin \davidhirtz\yii2\skeleton\base\Module
 */
interface ModuleInterface
{
    /**
     * @return array<string, DashboardPanelConfig>
     */
    public function getDashboardPanels(): array;

    public function getNavBarItems(): array;
}
