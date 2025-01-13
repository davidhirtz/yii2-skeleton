<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class ListGroup extends Tag
{
    protected array $attributes = [
        'class' => 'list-group list-unstyled',
    ];

    private array $items = [];

    public function addItem(ListGroupItemLink $link): static
    {
        if ($link->isVisible()) {
            $this->items[] = '<li class="list-group-item">' . $link->render() . '</li>';
        }

        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->items);
    }

    protected function renderTag(): string
    {
        return $this->items ? parent::renderTag() : '';
    }

    protected function getName(): string
    {
        return 'ul';
    }
}
