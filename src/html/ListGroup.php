<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class ListGroup extends Ul
{
    protected array $attributes = [
        'class' => 'list-group',
    ];

    protected array $itemAttributes = [
        'class' => 'list-group-item',
    ];

    public function addLink(ListGroupItemLink $link): static
    {
        if ($link->isVisible()) {
            $this->addItem($link->render(), $this->itemAttributes);
        }

        return $this;
    }
}
