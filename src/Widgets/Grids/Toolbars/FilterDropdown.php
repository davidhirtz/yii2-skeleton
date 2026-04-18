<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Toolbars;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Navs\DropdownOptionLink;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class FilterDropdown extends Widget
{
    use TagAttributesTrait;
    use LabelTrait;
    use TagIdTrait;
    use VisibilityTrait;

    protected int|false $showFilterThreshold = 20;
    protected array $params = ['page' => null];

    protected string $paramName;
    protected string|false|null $default = null;
    protected int|string|null $value = null;
    protected ?string $placeholder = null;
    protected ?bool $filterable = null;

    /**
     * @param array<int|string, string> $items
     */
    protected array $items = [];

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function default(string|false|null $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function filterable(?bool $filterable): static
    {
        $this->filterable = $filterable;
        return $this;
    }

    public function paramName(string $param): static
    {
        $this->paramName = $param;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->items || !$this->isVisible()) {
            return '';
        }

        $this->default ??= Yii::t('skeleton', 'Show All');
        $this->placeholder ??= Yii::t('skeleton', 'Filter ...');
        $this->value ??= Yii::$app->getRequest()->get($this->paramName);

        if ($this->showFilterThreshold !== false) {
            $this->filterable ??= count($this->items) >= $this->showFilterThreshold;
        }

        $dropdown = Dropdown::make()
            ->autofocus()
            ->label($this->value ? $this->items[$this->value] ?? $this->label : $this->label);

        if ($this->filterable) {
            $dropdown->content($this->getFilter());
        }

        if ($this->hasActiveItem()) {
            if ($this->default) {
                $dropdown->addItem(DropdownOptionLink::make()
                    ->class('dropdown-default-item')
                    ->current([...$this->params, $this->paramName => null])
                    ->text($this->default))
                    ->divider();
            }

            $dropdown->addClass('active');
        }

        foreach ($this->items as $param => $text) {
            $link = DropdownOptionLink::make()
                ->current([...$this->params, $this->paramName => $param])
                ->text($text);

            if ((string)$param === (string)$this->value) {
                $link->addClass('selected');

                $dropdown->addClass('active')
                    ->label($text);
            }

            $dropdown->addItem($link);
        }

        return GridToolbarItem::make()
            ->attributes($this->attributes)
            ->content($dropdown);
    }

    protected function getFilter(): Stringable
    {
        return Div::make()
            ->class('dropdown-header')
            ->content(TextInput::make()
                ->attribute('data-filter', '#' . $this->getId() . ' li')
                ->class('input')
                ->placeholder($this->placeholder)
                ->type('search'));
    }

    protected function hasActiveItem(): bool
    {
        return $this->value !== null && array_key_exists($this->value, $this->items);
    }
}
