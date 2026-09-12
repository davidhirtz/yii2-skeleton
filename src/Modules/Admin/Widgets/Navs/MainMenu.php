<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Override;
use Yii;

class MainMenu extends Nav
{
    protected Module $module;

    public function __construct(array $config = [])
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->addClass('aside-nav');

        $this->addItem(
            dashboard: DashboardNavItem::make(),
            user: UserNavItem::make(),
            system: SystemNavItem::make());

        foreach ($this->module->getSubmodules() as $module) {
            if ($module instanceof ModuleInterface) {
                $module->aside($this);
            }
        }

        parent::configure();
    }
}
