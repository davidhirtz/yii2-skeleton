<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use Override;

class Nav extends Tag
{
    public array $attributes = [
        'class' => 'nav',
    ];

    private array $items = [];
    private bool $hideSingleItem = false;

    public function addItems(Tag ...$tags): static
    {
        foreach ($tags as $tag) {
            $this->items[] = Li::make()
                ->class('nav-item')
                ->content($tag);
        }

        return $this;
    }

    public function hideSingleItem(bool $hideSingleItem = true): static
    {
        $this->hideSingleItem = $hideSingleItem;
        return $this;
    }

    #[Override]
    protected function renderContent(): string
    {
        return implode('', $this->items);
    }

    #[Override]
    protected function renderTag(): string
    {
        return $this->items && (!$this->hideSingleItem || count($this->items) > 1)
            ? parent::renderTag() :
            '';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'ul';
    }
}
