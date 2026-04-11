<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class Dropdown extends Widget
{
    use TagAttributesTrait;
    use TagContentTrait;

    protected bool $autofocus = false;
    protected Button $button;

    /**
     * @var Li[]
     */
    protected array $items = [];

    public function autofocus(bool $autofocus = true): static
    {
        $this->autofocus = $autofocus;
        return $this;
    }

    public function button(Button $button): static
    {
        $this->button = $button;
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
            $content = (string)$item;

            if ($content) {
                $this->items[] = Li::make()
                    ->content($content)
                    ->class('dropdown-item');
            }
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
            ->content(Div::make()->class('dropdown-divider'));

        return $this;
    }

    protected function configure(): void
    {
        $this->button->attributes['data-autofocus'] ??= $this->autofocus;

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $popover = Div::make()
            ->attribute('popover', 'auto')
            ->class('dropdown-menu')
            ->content(...$this->content)
            ->addContent(Ul::make()
                ->class('dropdown-list')
                ->content(...$this->items));

        $this->button->attributes['popovertarget'] = $popover->getId();

        return Div::make()
            ->attributes($this->attributes)
            ->addClass('dropdown')
            ->content($this->button, $popover);
    }
}
