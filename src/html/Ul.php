<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use Override;
use Stringable;

class Ul extends Tag
{
    protected array $items = [];

    public function addItem(string|Stringable $html, array $attributes = []): static
    {
        if (!$html instanceof Li) {
            $html = Li::make()
                ->attributes($attributes)
                ->content($html);
        }

        $this->items[] = $html;

        return $this;
    }

    public function items(array $items, array $attributes = []): static
    {
        $this->items = [];

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
