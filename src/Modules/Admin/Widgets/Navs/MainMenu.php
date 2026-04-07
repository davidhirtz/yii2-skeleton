<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class MainMenu extends Widget
{
    public array $attributes = [
        'class' => 'aside-nav nav',
    ];

    protected function renderContent(): Stringable
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');

        return $module->aside(Nav::make()
            ->attributes($this->attributes)
            ->showSingleItem());
    }
}
