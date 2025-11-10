<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\html\Dropdown;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Link;
use Stringable;
use Yii;

class FilterDropdown implements Stringable
{
    private readonly Dropdown $dropdown;

    public function __construct(
        public array $items,
        public string $label,
        public string $paramName,
        public string|false|null $defaultItem = null,
        public int|string|null $value = null,
        public bool $filter = false,
        public ?string $filterPlaceholder = null,
        public array $params = [
            'page' => null,
        ],
    ) {
        $this->dropdown = Dropdown::make()
            ->addAttributes(['hx-boost' => 'true']);
    }

    public function render(): string
    {
        if (!$this->items) {
            return '';
        }

        $this->defaultItem ??= Yii::t('skeleton', 'Show All');
        $this->filterPlaceholder ??= Yii::t('skeleton', 'Filter ...');
        $this->value ??= Yii::$app->getRequest()->get($this->paramName);

        $this->dropdown->label($this->items[$this->value] ?? $this->label);

        if ($this->filter) {
            $this->addFilterInput();
        }

        if ($this->hasActiveItem()) {
            if ($this->defaultItem) {
                $this->addDefaultItem();
            }

            $this->dropdown->addClass('active');
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

            if ($param === $this->value) {
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

    public function __toString(): string
    {
        return $this->render();
    }
}
