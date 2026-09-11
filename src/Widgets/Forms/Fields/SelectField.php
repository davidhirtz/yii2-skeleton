<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Option;
use Hirtz\Skeleton\Html\Select;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Override;
use Stringable;
use yii\helpers\Inflector;

class SelectField extends Field
{
    use TagInputTrait;

    /**
     * @var array<string|int, string|int|array>
     */
    protected array $items = [];

    protected string|false $prompt = false;
    protected array $promptAttributes = [];
    protected bool $multiple = false;

    /**
     * @var array<int|string, array> extra attributes per option value, also applied to an item built from the model
     */
    protected array $itemAttributes = [];

    /**
     * @param array<int|string, array> $itemAttributes
     */
    public function itemAttributes(array $itemAttributes): static
    {
        $this->itemAttributes = $itemAttributes;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function prompt(string|false $prompt = '', array $attributes = []): static
    {
        $this->prompt = $prompt;
        $this->promptAttributes = $attributes;
        return $this;
    }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function addItem(string|int $value, string|int|array $item): static
    {
        $this->items[$value] = $item;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->model) {
            if (!$this->items) {
                $method = 'get' . Inflector::camelize(Inflector::pluralize($this->property));

                /** @var array<int|string, string|array> $items */
                $items = $this->model->hasMethod($method)
                    ? call_user_func([$this->model, $method])
                    : [];

                foreach ($items as $key => $item) {
                    $this->items[$key] = is_array($item)
                        ? ['label' => $item['name'], ...$item['attributes'] ?? []]
                        : $item;
                }

                $attributes = array_filter(array_map(
                    function (array|string $options) {
                        if (is_string($options)) {
                            return [];
                        }

                        $attributes = $options['hiddenFields'] ?? [];

                        return $this->model instanceof I18nAttributeInterface
                            ? $this->model->getI18nAttributesNames($attributes)
                            : $attributes;
                    },
                    $items
                ));

                if ($attributes) {
                    $selectors = [];

                    foreach ($attributes as $value => $names) {
                        $selectors["$value"] = array_map(
                            fn (string $name) => $this->model->hasProperty($name)
                                ? Html::getInputId($this->model, $name)
                                : $name,
                            $names
                        );
                    }

                    $this->attributes['data-toggle'] ??= $selectors;
                }
            }

            if ($this->property) {
                $this->attributes['value'] ??= $this->model->{$this->property};
            }
        }

        parent::configure();

        if ($this->multiple) {
            $this->attributes['multiple'] = true;
            $this->attributes['name'] = "{$this->attributes['name']}[]";
        }
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if ($this->multiple || count($this->items) > 1 || !$this->isRequired()) {
            return parent::renderContent();
        }

        return Input::make()
            ->attributes($this->attributes)
            ->type('hidden');
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        $value = $this->attributes['value'] ?? $this->model->{$this->property} ?? '';
        $selected = array_map(strval(...), is_array($value) ? $value : [$value]);
        unset($this->attributes['value']);

        $select = Select::make()
            ->attributes($this->attributes)
            ->addClass('input');

        if (false !== $this->prompt && !$this->multiple && (!$this->isRequired() || [''] !== $selected)) {
            $this->promptAttributes['disabled'] ??= $this->isRequired();

            $select->addOption(Option::make()
                ->attributes($this->promptAttributes)
                ->label($this->prompt));
        }

        foreach ($this->items as $value => $attributes) {
            $value = (string)$value;

            if (is_array($attributes)) {
                $label = $attributes['label'] ?? $value;
                unset($attributes['label']);
            } else {
                $label = (string)$attributes;
                $attributes = [];
            }

            $select->addOption(Option::make()
                ->attributes([...$attributes, ...$this->itemAttributes[$value] ?? []])
                ->label($label)
                ->selected(in_array($value, $selected, true))
                ->value($value));
        }

        return $select;
    }
}
