<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\Option;
use davidhirtz\yii2\skeleton\html\Select;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\models\interfaces\I18nAttributeInterface;
use Override;
use Stringable;
use yii\helpers\Inflector;

class SelectField extends Field
{
    use TagInputTrait;

    public bool $showSingleOption = false;

    /**
     * @var array<string|int, string|int|array>
     */
    protected array $items = [];

    protected string|false $prompt = false;
    protected array $promptAttributes = [];

    public function showSingleOption(bool $showSingleOption = true): static
    {
        $this->showSingleOption = $showSingleOption;
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

    #[\Override]
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
                    function (array $options) {
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
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return count($this->items) === 1 && false === $this->showSingleOption
            ? Input::make()
                ->attributes($this->attributes)
                ->type('hidden')
            : parent::renderContent();
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        $selected = (string)($this->attributes['value'] ?? $this->model->{$this->property} ?? '');
        unset($this->attributes['value']);

        $select = Select::make()
            ->attributes($this->attributes)
            ->addClass('input');

        if (false !== $this->prompt && (!$this->isRequired() || '' !== $selected)) {
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
                ->attributes($attributes)
                ->label($label)
                ->selected($value === $selected)
                ->value($value));
        }

        return $select;
    }
}
