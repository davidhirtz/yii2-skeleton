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
     * @param T[]|Closure(T[]):T[] $items
     * @return $this
     */
    public function items(array|Closure $items): static
    {
        $this->items = $items instanceof Closure ? $items($this->items) : array_values(array_filter($items));
        return $this;
    }

    /**
     * @param T|null ...$items
     */
    public function addItem(?Stringable ...$items): static
    {
        $this->items = [...$this->items, ...array_filter($items)];
        return $this;
    }
}
