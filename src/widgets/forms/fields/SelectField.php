<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\base\VoidTag;
use davidhirtz\yii2\skeleton\html\Option;
use davidhirtz\yii2\skeleton\html\Select;
use Override;
use Stringable;

class SelectField extends Field
{
    /**
     * @var array<string|int, string|int|array{label: string, attributes?: array<string|int>}>
     */
    public array $items = [];

    protected string|false $empty = false;

    public function empty(string|false $empty = ''): static
    {
        $this->empty = $empty;
        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function addItem(string|int $value, string|int|array $label): static
    {
        $this->items[$value] = $label;
        return $this;
    }

    #[Override]
    public function getInput(): string|Stringable
    {
        $this->attributes['id'] ??= $this->getId();

        $select = Select::make()
            ->attributes($this->attributes)
            ->addClass('input');

        if (false !== $this->empty && (!$this->isRequired() || empty($this->attributes['value']))) {
            $select->addOption(Option::make()
                ->disabled($this->isRequired())
                ->label($this->empty));
        }

        foreach ($this->items as $value => $item) {
            $select->addOption(Option::make()
                ->attributes($item['attributes'] ?? [])
                ->label($item['label'] ?? $item)
                ->value($value));
        }

        return $select;
    }
}