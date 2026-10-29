<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Checkbox;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Override;
use Stringable;

/**
 * The hidden input in front of the checkboxes makes "nothing checked" reach the model, since an unchecked checkbox
 * posts nothing.
 */
class CheckboxListField extends Field
{
    use TagInputTrait;

    /**
     * @var array<int|string, string|Stringable>
     */
    protected array $items = [];

    /**
     * On the label, since a tooltip is placed against its element's box and the label's box is the text.
     *
     * @var array<int|string, array<string, mixed>>
     */
    protected array $itemAttributes = [];

    /**
     * @param array<int|string, string|Stringable> $items
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function addItem(string|int $value, string|Stringable $label): static
    {
        $this->items[$value] = $label;
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

    /**
     * No `for`: the caption labels no single input, and one pointing at an id never rendered moves the focus nowhere.
     */
    #[Override]
    protected function getLabel(): ?Div
    {
        return $this->label
            ? Div::make()
                ->attributes($this->labelAttributes)
                ->addClass('label')
                ->text($this->label)
            : null;
    }

    #[Override]
    protected function getInput(): string|Stringable
    {
        $name = (string)($this->attributes['name'] ?? '');
        $value = $this->attributes['value'] ?? ($this->model ? $this->model->{$this->property} : []);
        $checked = array_map(strval(...), is_array($value) ? $value : [$value]);

        $attributes = $this->attributes;
        unset($attributes['name'], $attributes['id'], $attributes['value'], $attributes['required']);

        $content = (string)Input::make()
            ->attributes([
                'type' => 'hidden',
                'name' => $name,
                'value' => '',
            ]);

        foreach ($this->items as $itemValue => $label) {
            $id = Html::getInputIdByName("{$name}[$itemValue]");

            $content .= Div::make()
                ->addClass('form-checkbox')
                ->content(
                    Div::make()
                        ->addClass('checkbox')
                        ->content(Checkbox::make()
                            ->attributes([
                                ...$attributes,
                                'id' => $id,
                                'name' => "{$name}[]",
                                'value' => (string)$itemValue,
                            ])
                            ->addClass('input')
                            ->checked(in_array((string)$itemValue, $checked, true))),
                    Label::make()
                        ->attributes($this->itemAttributes[$itemValue] ?? [])
                        ->addClass('label')
                        ->for($id)
                        ->text($label)
                );
        }

        return $content;
    }
}
