<?php

namespace davidhirtz\yii2\skeleton\modules\admin;

/**
 * @mixin \davidhirtz\yii2\skeleton\base\Module
 */
interface ModuleInterface
{
    public function getDashboardPanels(): array;

    public function getNavBarItems(): array;
}
