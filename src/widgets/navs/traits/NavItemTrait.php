<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\navs\traits;

use davidhirtz\yii2\skeleton\widgets\navs\NavItem;

trait NavItemTrait
{
    /**
     * @var NavItem[]
     */
    protected array $items = [];

    public function items(NavItem|null ...$items): static
    {
        $this->items = array_filter($items);
        return $this;
    }

    public function addItem(NavItem $item): static
    {
        $this->items[] = $item;
        return $this;
    }
}
