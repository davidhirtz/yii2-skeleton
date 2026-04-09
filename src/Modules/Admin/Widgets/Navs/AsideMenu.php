<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Widget;
use Hirtz\Skeleton\Html\Aside;
use Stringable;

class AsideMenu extends Widget
{
    public array $attributes = [
        'class' => 'aside',
        'id' => 'aside',
    ];

    protected function renderContent(): string|Stringable
    {
        return Aside::make()
            ->attributes($this->attributes)
            ->content($this->getMainMenu(), $this->getAccountMenu());
    }

    protected function getMainMenu(): Stringable
    {
        return MainMenu::make();
    }

    protected function getAccountMenu(): AccountMenu
    {
        return AccountMenu::make();
    }
}
