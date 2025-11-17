<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\panels;

use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class ListGroup extends Widget
{
    use TagAttributesTrait;

    protected array $items = [];

    public function items(ListGroupItem|null ...$items): static
    {
        $this->items = array_values(array_filter($items));
        return $this;
    }

    public function addItem(ListGroupItem $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $items = array_filter($this->items, fn (ListGroupItem $item) => $item->isVisible());

        if (!$items) {
            return '';
        }

        return Ul::make()
            ->attributes($this->attributes)
            ->addClass('list-group')
            ->content(...$this->items);
    }
}
