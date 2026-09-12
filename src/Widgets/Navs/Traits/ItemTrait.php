<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs\Traits;

use Closure;
use Stringable;

/**
 * @template T of Stringable
 */
trait ItemTrait
{
    /**
     * @var T[]
     */
    protected array $items = [];

    /**
     * @param array<T|null>|Closure(T[]):T[] $items
     * @return $this
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items instanceof Closure ? $items($this->items) : array_filter($items);
        return $this;
    }

    /**
     * Named arguments arrive as string keys, which `removeItem()` and a later `addItem()` address the item by.
     *
     * @param T|null ...$items
     */
    public function addItem(?Stringable ...$items): static
    {
        $this->items = [...$this->items, ...array_filter($items)];
        return $this;
    }

    public function removeItem(string ...$names): static
    {
        foreach ($names as $name) {
            unset($this->items[$name]);
        }

        return $this;
    }
}
