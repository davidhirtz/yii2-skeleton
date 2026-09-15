<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Aside;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AsideMenu extends Widget
{
    public array $attributes = [
        'class' => 'aside hidden-empty',
        'id' => 'aside',
    ];

    #[Override]
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
