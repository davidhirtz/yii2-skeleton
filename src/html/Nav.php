<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Nav extends Tag
{
    protected array $attributes = [
        'class' => 'nav',
    ];

    private array $items = [];
    private bool $hideSingleItem = false;

    public function addItem(Tag $tag): static
    {
        $this->items[] = '<li class="nav-item">' . $tag->render() . '</li>';
        return $this;
    }

    public function hideSingleItem(bool $hideSingleItem = true): static
    {
        $this->hideSingleItem = $hideSingleItem;
        return $this;
    }

    protected function renderContent(): string
    {
        return implode('', $this->items);
    }

    protected function renderTag(): string
    {
        return $this->items && (!$this->hideSingleItem || count($this->items) > 1) ? parent::renderTag() : '';
    }

    protected function getName(): string
    {
        return 'ul';
    }
}
