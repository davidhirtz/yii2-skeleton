<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Aside extends Widget
{
    public array $attributes = [
        'class' => 'aside',
        'id' => 'aside',
    ];

    protected function renderContent(): string|Stringable
    {
        return \Hirtz\Skeleton\Html\Aside::make()
            ->attributes($this->attributes)
            ->content($this->renderMainMenu(), $this->renderAccountMenu());
    }

    protected function renderMainMenu(): Stringable
    {
        return MainMenu::make();
    }

    protected function renderAccountMenu(): AccountMenu
    {
        return AccountMenu::make();
    }
}
