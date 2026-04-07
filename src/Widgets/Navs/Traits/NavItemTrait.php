<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs\Traits;

use Closure;
use Hirtz\Skeleton\Widgets\Navs\NavItem;

trait NavItemTrait
{
    /**
     * @var NavItem[]
     */
    protected array $items = [];

    /**
     * @param NavItem[]|Closure(NavItem[]):NavItem[] $items
     * @return $this
     */
    public function items(array|Closure $items): static
    {
        $this->items = is_callable($items) ? $items($this->items) : array_filter($items);
        return $this;
    }

    public function addItem(NavItem $item): static
    {
        $this->items[] = $item;
        return $this;
    }
}
