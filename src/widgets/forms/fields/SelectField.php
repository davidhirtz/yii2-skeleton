<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Option;
use davidhirtz\yii2\skeleton\html\Select;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Override;
use Stringable;
use Yii;
use yii\helpers\Inflector;

class SelectField extends Field
{
    use TagInputTrait;

    /**
     * @var array<string|int, string|int|array{label: string, attributes?: array<string|int>}>
     */
    public array $items = [];

    protected string|false $prompt = false;
    protected array $promptAttributes = [];

    public function prompt(string|false $prompt = ''): static
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function promptAttributes(array $options): static
    {
        $this->promptAttributes = $options;
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
    protected function getInput(): string|Stringable
    {
        $this->attributes['id'] ??= $this->getId();

        $selected = $this->attributes['value'] ?? $this->model->{$this->property} ?? null;
        unset($this->attributes['value']);

        if (!$this->items && $this->model) {
            $method = 'get' . Inflector::camelize(Inflector::pluralize($this->property));

            /** @var array<int|string, string|array> $items */
            $items = $this->model->hasMethod($method)
                ? call_user_func([$this->model, $method])
                : [];

            if (!$items) {
                Yii::debug('No items returned from ' . $this->model::class . '::' . $method . '()');
            }

            $this->items = is_array(current($items))
                ? array_map(fn ($item) => $item['name'] ?? current($item), $items)
                : $items;
        }

        $select = Select::make()
            ->attributes($this->attributes)
            ->addClass('input');

        if (false !== $this->prompt && (!$this->isRequired() || $selected)) {
            $this->promptAttributes['disabled'] ??= $this->isRequired();

            $select->addOption(Option::make()
                ->attributes($this->promptAttributes)
                ->label($this->prompt));
        }

        foreach ($this->items as $value => $item) {
            $attributes = $item['attributes'] ?? [];
            $attributes['selected'] = ((string)$value === (string)$selected);

            $select->addOption(Option::make()
                ->attributes($attributes)
                ->label($item['label'] ?? $item)
                ->value($value));
        }

        return $select;
    }
}
