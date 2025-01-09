<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

class Dropdown extends Tag
{
    protected array $attributes = ['class' => 'dropdown'];
    private Button $button;
    private array $items = [];

    public function button(Button $button): static
    {
        $this->button = $button->attribute('data-dropdown', '');
        return $this;
    }

    public function label(string $text): static
    {
        return $this->button(Button::make()->class('btn dropdown-toggle')->text($text));
    }

    public function dropend(): static
    {
        return $this->addClass('dropdown-menu-end');
    }

    public function dropup(): static
    {
        return $this->addClass('dropup');
    }

    public function addItem(Tag ...$items): static
    {
        foreach ($items as $item) {
            $this->items[] = '<li>' . $item->addClass('dropdown-item')->render() . '</li>';
        }

        return $this;
    }

    public function items(Tag ...$items): static
    {
        $this->items = [];
        return $this->addItem(...$items);
    }

    public function divider(): static
    {
        $this->items[] = '<li><div class="dropdown-divider"></div></li>';
        return $this;
    }

    protected function renderContent(): string
    {
        return $this->button->render() . '<dialog class="dropdown-menu"><ul>' . implode('', $this->items) . '</ul></dialog>';
    }
}
