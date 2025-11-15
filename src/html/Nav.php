<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;

class Nav extends Tag
{
    public array $attributes = [
        'class' => 'nav',
    ];

    private array $items = [];
    private bool $hideSingleItem = false;

    public function addItem(Tag $tag): static
    {
        $this->items[] = '<li class="nav-item">' . $tag->render() . '</li>';
        return $this;
    }

    public function addItems(array $items): static
    {
        foreach ($items as $item) {
            if ($item instanceof Tag) {
                $this->addItem($item);
            }
        }

        return $this;
    }

    public function hideSingleItem(bool $hideSingleItem = true): static
    {
        $this->hideSingleItem = $hideSingleItem;
        return $this;
    }

    #[\Override]
    protected function renderContent(): string
    {
        return implode('', $this->items);
    }

    #[\Override]
    protected function renderTag(): string
    {
        return $this->items && (!$this->hideSingleItem || count($this->items) > 1) ? parent::renderTag() : '';
    }

    #[\Override]
    protected function getTagName(): string
    {
        return 'ul';
    }
}
