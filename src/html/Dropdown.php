<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;

class Dropdown extends Tag
{
    use TagContentTrait;

    public array $attributes = ['class' => 'dropdown'];

    private Button $button;
    private array $items = [];

    public function button(Button $button): static
    {
        $this->button = $button->attribute('data-dropdown', '');
        return $this;
    }

    public function label(string $text): static
    {
        return $this->button(Button::make()
            ->class('btn dropdown-toggle')
            ->text($text));
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

    #[\Override]
    protected function renderContent(): string
    {
        $content = implode('', $this->content) . '<ul>' . implode('', $this->items) . '</ul>';
        return $this->button->render() . '<dialog class="dropdown-menu">' . $content . '</dialog>';
    }
}
