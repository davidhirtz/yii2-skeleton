<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Closure;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Attributes\Configure;
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

    protected array $items = [];

    private ?array $popoverCallbacks = null;

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

    /**
     * @param Closure(Div):Div $callback
     */
    public function popover(Closure $callback): static
    {
        $this->popoverCallbacks[] = $callback;
        return $this;
    }

    public function dropup(): static
    {
        return $this->addClass('dropup');
    }

    public function items(array $items): static
    {
        $this->items = array_values(array_filter($items));
        return $this;
    }

    public function addItem(string|Stringable ...$items): static
    {
        $this->items = [...$this->items, ...array_filter($items)];
        return $this;
    }

    public function divider(): static
    {
        return $this->addItem(Li::make()
            ->content(Div::make()->class('dropdown-divider')));
    }

    #[Configure]
    protected function configure(): void
    {
        $this->button->attributes['data-autofocus'] ??= $this->autofocus;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $content = $this->getListContent();

        if (!$content) {
            return '';
        }

        $popover = $this->evaluate($this->popoverCallbacks, Div::make()
            ->attribute('popover', 'auto')
            ->class('dropdown-menu')
            ->content(...$this->content)
            ->addContent(Ul::make()
                ->class('dropdown-list')
                ->content(...$content)));

        $this->button->attributes['popovertarget'] = $popover->getId();

        return Div::make()
            ->attributes($this->attributes)
            ->addClass('dropdown')
            ->content($this->button, $popover);
    }

    protected function getListContent(): array
    {
        $items = [];

        foreach ($this->items as $item) {
                $items[] = $item instanceof Li
                    ? $item->render()
                    : Li::make()->class('dropdown-item')->content($item)->render();
        }

        return array_filter($items);
    }
}
