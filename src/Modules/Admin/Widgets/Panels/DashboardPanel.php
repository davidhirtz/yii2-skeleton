<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Closure;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Panels\Panel;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;

class DashboardPanel extends Panel
{
    use LabelTrait;
    use TagAttributesTrait;

    /**
     * @var StackItem[]
     */
    protected array $items = [];

    /**
     * @param StackItem[]|Closure(StackItem[]):StackItem[] $items
     * @return $this
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items instanceof Closure ? $items($this->items) : array_values(array_filter($items));
        return $this;
    }

    public function addItem(StackItem ...$items): static
    {
        $this->items = [...$this->items, ...array_filter($items)];
        return $this;
    }

    protected function renderContent(): Stack
    {
        $list = Stack::make();

        foreach ($this->items as $item) {
            $list->addItem(StackItem::make()
                ->attributes($item->attributes)
                ->label($item->label)
                ->url($item->url)
                ->roles($item->roles)
                ->icon($item->icon));
        }

        return $list;
    }
}
