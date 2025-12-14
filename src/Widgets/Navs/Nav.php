<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Navs\Traits\NavItemTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Nav extends Widget
{
    use NavItemTrait;
    use TagAttributesTrait;

    protected bool $hideSingleItem = true;

    public function showSingleItem(): static
    {
        $this->hideSingleItem = false;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $items = array_filter($this->items, fn (NavItem $item) => $item->isVisible());

        if (!$items || (1 === count($items) && $this->hideSingleItem)) {
            return '';
        }

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('nav')
            ->content(...$items);
    }
}
