<?php

namespace davidhirtz\yii2\skeleton\html;

use Yiisoft\Html\Tag\Base\Tag;
use Yiisoft\Html\Tag\Li;

final class ListGroup extends Tag
{
    private array $items = [];

    public static function tag(): self
    {
        $self = new self();
        $self->attributes['class'] = 'list-group list-unstyled';
        return $self;
    }

    public function item(ListGroupItemAction $link): self
    {
        if ($link->isVisible()) {
            $this->items[] = Li::tag()->content($link)->render();
        }

        return $this;
    }

    protected function getName(): string
    {
        return 'ul';
    }

    protected function renderTag(): string
    {
        return $this->items ? '<' . $this->getName() . $this->renderAttributes() . '>' . implode('', $this->items) . '</' . $this->getName() . '>' : '';
    }
}
