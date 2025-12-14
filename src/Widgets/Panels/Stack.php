<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class Stack extends Widget
{
    use TagAttributesTrait;

    protected array $items = [];

    public function items(StackItem|null ...$items): static
    {
        $this->items = array_values(array_filter($items));
        return $this;
    }

    public function addItem(StackItem $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $items = array_filter($this->items, fn (StackItem $item) => $item->isVisible());

        if (!$items) {
            return '';
        }

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('stack')
            ->content(...$this->items);
    }
}
