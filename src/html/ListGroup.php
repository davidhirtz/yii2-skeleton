<?php

namespace davidhirtz\yii2\skeleton\html;

use Yiisoft\Html\Tag\Base\NormalTag;
use Yiisoft\Html\Tag\Li;

final class ListGroup extends NormalTag
{
    protected array $attributes = ['class' => 'list-group list-unstyled'];
    private array $items = [];

    public function link(ListGroupItemAction $link): self
    {
        $this->items[] = Li::tag()->content($link)->render();
        return $this;
    }

    protected function generateContent(): string
    {
        return implode('', $this->items);
    }

    protected function getName(): string
    {
        return 'ul';
    }
}
