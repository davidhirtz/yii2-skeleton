<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Dialog;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Dropdown extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

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
            ->class('dropdown-btn')
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

    public function addItem(string|Stringable ...$items): static
    {
        foreach (array_filter($items) as $item) {
            $this->items[] = Li::make()
                ->content($item)
                ->class('dropdown-item');
        }

        return $this;
    }

    public function items(string|Stringable ...$items): static
    {
        $this->items = [];
        return $this->addItem(...$items);
    }

    public function divider(): static
    {
        $this->items[] = Li::make()
            ->content(Div::make()
                ->class('dropdown-divider'));

        return $this;
    }

    #[Override]
    protected function renderContent(): Stringable
    {
        $dialog = Dialog::make()
            ->class('dropdown-menu')
            ->content(...$this->content)
            ->addContent(Ul::make()
                ->content(...$this->items));

        return Div::make()
            ->attributes($this->attributes)
            ->addClass('dropdown')
            ->content($this->button, $dialog);
    }
}
