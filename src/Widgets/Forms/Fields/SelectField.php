<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Optgroup;
use Hirtz\Skeleton\Html\Option;
use Hirtz\Skeleton\Html\Select;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Models\Definitions\Definition;
use Override;
use Stringable;
use Yii;
use yii\helpers\Inflector;

class SelectField extends Field
{
    use TagInputTrait;

    /**
     * @var array<int|string, string|int|array<string, mixed>> an item with an `items` key is an option group,
     * labelled by its `label`
     */
    protected array $items = [];

    protected string|false $prompt = false;
    /**
     * @var array<string, mixed>
     */
    protected array $promptAttributes = [];
    protected bool $multiple = false;

    /**
     * @var array<int|string, array<string, mixed>> extra attributes per option value, also applied to an item
     * built from the model
     */
    protected array $itemAttributes = [];

    /**
     * @param array<string, mixed> $promptAttributes
     */
    public function promptAttributes(array $promptAttributes): static
    {
        $this->promptAttributes = $promptAttributes;
        return $this;
    }

    /**
     * @param array<int|string, array<string, mixed>> $itemAttributes
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

    /**
     * @param array<string, mixed> $attributes
     */
    public function prompt(string|false $prompt = '', array $attributes = []): static
    {
        $this->prompt = $prompt;
        $this->promptAttributes = $attributes;
        return $this;
    }

    /**
     * @param array<int|string, mixed> $items
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @param array<string, mixed>|string|int $item
     */
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
                $this->addItemsFromModel($this->getItemsFromModel());
            }

            if ($this->property) {
                $this->attributes['value'] ??= $this->model->{$this->property};
            }
        }

        parent::configure();

        if ($this->multiple) {
            $this->attributes['multiple'] = true;
            $this->attributes['name'] = "{$this->attributes['name']}[]";
            $this->attributes['data-multiselect'] ??= Yii::t('skeleton', 'SELECT_FIELD_REMOVE');
        }
    }

    /**
     * @return array<int|string, Definition|string|int|mixed[]>
     */
    protected function getItemsFromModel(): array
    {
        $model = $this->model;

        if ($model === null) {
            return [];
        }

        $method = 'get' . Inflector::camelize($this->property) . 'Definitions';

        if (!$model->hasMethod($method)) {
            $method = 'get' . Inflector::camelize(Inflector::pluralize($this->property));
        }

        return $model->hasMethod($method) ? $model->$method() : [];
    }

    /**
     * @param array<int|string, Definition|string|int|mixed[]> $items
     */
    protected function addItemsFromModel(array $items): void
    {
        foreach ($items as $key => $item) {
            $this->items[$key] = $item instanceof Definition ? $item->getName() : $item;
        }
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (!$this->isHiddenInput()) {
            return parent::renderContent();
        }

        return Input::make()
            ->attributes($this->attributes)
            ->type('hidden');
    }

    /**
     * A required single choice leaves nothing to pick, so it is posted as a hidden input without a row.
     */
    protected function isHiddenInput(): bool
    {
        return !$this->multiple && $this->getOptionCount() <= 1 && $this->isRequired();
    }

    protected function getOptionCount(): int
    {
        $count = 0;

        foreach ($this->items as $item) {
            $count += is_array($item) && is_array($item['items'] ?? null) ? count($item['items']) : 1;
        }

        return $count;
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

            // An option without a `value` posts its label, so a prompt carrying one is submitted as that text.
            $this->promptAttributes['value'] ??= '';

            $select->addOption(Option::make()
                ->attributes($this->promptAttributes)
                ->label($this->prompt));
        }

        foreach ($this->items as $value => $attributes) {
            if (is_array($attributes) && is_array($attributes['items'] ?? null)) {
                $group = Optgroup::make()
                    ->attribute('label', (string)($attributes['label'] ?? $value));

                foreach ($attributes['items'] as $itemValue => $itemAttributes) {
                    $group->addOption($this->getOption((string)$itemValue, $itemAttributes, $selected));
                }

                $select->addOption($group);
                continue;
            }

            $select->addOption($this->getOption((string)$value, $attributes, $selected));
        }

        if (!$this->multiple || !empty($this->attributes['disabled'])) {
            return $select;
        }

        // An unselected option posts nothing, so without it a list emptied in the form never reaches the model
        $empty = Input::make()
            ->type('hidden')
            ->attribute('name', substr((string)($this->attributes['name'] ?? ''), 0, -2))
            ->value('');

        return $empty . $select;
    }

    /**
     * @param array<string> $selected
     */
    protected function getOption(string $value, mixed $attributes, array $selected): Option
    {
        if (is_array($attributes)) {
            $label = (string)($attributes['label'] ?? $value);
            unset($attributes['label']);
        } else {
            $label = (string)$attributes;
            $attributes = [];
        }

        return Option::make()
            ->attributes([...$attributes, ...$this->itemAttributes[$value] ?? []])
            ->label($label)
            ->selected(in_array($value, $selected, true))
            ->value($value);
    }
}
