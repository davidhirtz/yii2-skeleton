<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Ul extends Tag
{
    private array $items = [];

    public function addItem(string $html, array $attributes = []): static
    {
        $this->items[] = Li::make()
            ->attributes($attributes)
            ->html($html)
            ->render();

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
