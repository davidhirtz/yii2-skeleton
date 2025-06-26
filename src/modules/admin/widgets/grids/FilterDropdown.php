<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Link;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;

class FilterDropdown extends Widget
{
    public string|false|null $defaultItem = null;
    public bool $filter = false;
    public ?string $filterPlaceholder = null;
    public string $label;
    public array $items = [];
    public string $paramName;

    public array $params = [
        'page' => null,
    ];

    public int|string|null $value = null;

    private Dropdown $dropdown;

    public function init(): void
    {
        $this->dropdown = Dropdown::make();
    }

    public function render(): string
    {
        if (!$this->items) {
            return '';
        }

        $this->dropdown->label($this->label);

        $this->defaultItem ??= Yii::t('skeleton', 'Show All');
        $this->filterPlaceholder ??= Yii::t('skeleton', 'Filter ...');
        $this->value ??= Yii::$app->request->get($this->paramName);

        if ($this->filter) {
            $this->addFilterInput();
        }

        if ($this->defaultItem && $this->hasActiveItem()) {
            $this->addDefaultItem();
        }

        $this->addItems();

        return $this->dropdown->render();
    }

    protected function addFilterInput(): void
    {
        $input = Input::make()
            ->attribute('data-filter', '#' . $this->dropdown->getId() . ' li')
            ->placeholder($this->filterPlaceholder)
            ->type('search')
            ->render();

        $this->dropdown->html('<div class="dropdown-header">' . $input . '</div>');
    }

    protected function addDefaultItem(): void
    {
        $this->dropdown->addItem(Link::make()
            ->class('dropdown-default-item')
            ->current([...$this->params, $this->paramName => null])
            ->text($this->defaultItem));

        $this->dropdown->divider();
    }

    protected function addItems(): void
    {
        foreach ($this->items as $param => $text) {
            $link = Link::make()
                ->current([...$this->params, $this->paramName => $param])
                ->text($text);

            if ($param == $this->value) {
                $this->dropdown->addClass('active')->label($text);
                $link->addClass('active');
            }

            $this->dropdown->addItem($link);
        }
    }

    protected function hasActiveItem(): bool
    {
        return array_key_exists($this->value, $this->items);
    }
}
