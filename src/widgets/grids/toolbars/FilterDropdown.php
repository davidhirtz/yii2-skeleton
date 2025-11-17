<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\toolbars;

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\DropdownLink;
use davidhirtz\yii2\skeleton\html\TextInput;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\traits\TagLabelTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;

class FilterDropdown extends Widget
{
    use TagAttributesTrait;
    use TagLabelTrait;
    use TagIdTrait;

    public int $showFilterThreshold = 20;
    public array $params = ['page' => null];

    protected string $param;
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

    public function param(string $param): static
    {
        $this->param = $param;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function renderContent(): string|Stringable
    {
        if (!$this->items) {
            return '';
        }

        $this->default ??= Yii::t('skeleton', 'Show All');
        $this->filterable ??= count($this->items) >= $this->showFilterThreshold;
        $this->placeholder ??= Yii::t('skeleton', 'Filter ...');
        $this->value ??= Yii::$app->getRequest()->get($this->param);

        $this->attributes['hx-boost'] ??= 'true';

        $dropdown = Dropdown::make()
            ->label($this->items[$this->value] ?? $this->label);

        if ($this->filterable) {
            $dropdown->content($this->getFilter());
        }

        if ($this->hasActiveItem()) {
            if ($this->default) {
                $dropdown->addItem($this->getDefault())
                    ->divider();
            }

            $dropdown->addClass('active');
        }

        foreach ($this->items as $param => $text) {
            $link = DropdownLink::make()
                ->current([...$this->params, $this->param => $param])
                ->text($text);

            if ($param === $this->value) {
                $link->addClass('active');

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
                ->placeholder($this->placeholder)
                ->type('search'));
    }

    protected function getDefault(): Stringable
    {
        return A::make()
            ->class('dropdown-default-item')
            ->current([...$this->params, $this->param => null])
            ->text($this->default);
    }

    protected function hasActiveItem(): bool
    {
        return array_key_exists($this->value, $this->items);
    }
}
