<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class MainMenu extends Widget
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
    protected function renderContent(): Stringable
    {
        return $this->module->aside($this->getNav());
    }

    protected function getNav(): Nav
    {
        return Nav::make()
            ->class('aside-nav');
    }
}
