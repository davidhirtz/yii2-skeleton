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
 * A checkbox per item for an attribute holding a list of values. The hidden input in front of them is what makes
 * "nothing checked" reach the model at all — a checkbox that is not checked posts nothing.
 */
class CheckboxListField extends Field
{
    use TagInputTrait;

    /**
     * @var array<int|string, string|Stringable>
     */
    protected array $items = [];

    /**
     * @var array<int|string, array<string, mixed>> attributes for an item's label, which is what a tooltip or a
     * state class has to sit on: a tooltip is placed against its element's box, and the label is the only part
     * of a checkbox row whose box is the text the reader is looking at
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
     * The group's caption labels no single input, so it must not claim one — a `for` pointing at an id that is
     * never rendered moves the focus nowhere.
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
