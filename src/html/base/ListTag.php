<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\base;

use Hirtz\Skeleton\html\Li;
use Override;
use Stringable;

abstract class ListTag extends Tag
{
    protected array $items = [];

    final public function items(string|Stringable ...$items): static
    {
        $this->items = [];
        return $this->addItem(...$items);
    }

    final public function addItem(string|Stringable ...$items): static
    {
        $items = array_values(array_filter($items));

        foreach ($items as $item) {
            $this->items[] = Li::make()->content($item);
        }

        return $this;
    }

    final public function content(string|Stringable|null ...$content): static
    {
        $this->items = array_values(array_filter($content));
        return $this;
    }

    final public function addContent(string|Stringable|null ...$content): static
    {
        $this->items = [...$this->items, ...array_values(array_filter($content))];
        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return implode('', $this->items);
    }

    #[Override]
    protected function getTag(): string
    {
        return $this->items ? parent::getTag() : '';
    }
}
