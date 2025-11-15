<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use Override;
use Stringable;

class Ul extends Tag
{
    private array $items = [];

    public static function tag(array $items, array $attributes = []): string
    {
        return static::make()
            ->attributes($attributes)
            ->items($items)
            ->render();
    }

    public function addItem(string|Stringable $html, array $attributes = []): static
    {
        $this->items[] = Li::make()
            ->attributes($attributes)
            ->content($html)
            ->render();

        return $this;
    }

    public function items(array $items, array $attributes = []): static
    {
        foreach ($items as $item) {
            $this->addItem($item, $attributes);
        }

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
        return $this->items ? parent::renderTag() : '';
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'ul';
    }
}
