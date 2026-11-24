<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Html\Checkbox;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Widgets\Forms\FormRow;
use Hirtz\Skeleton\Widgets\Traits\TooltipAttributeTrait;
use Override;
use Stringable;
use yii\validators\BooleanValidator;

class CheckboxField extends Field
{
    use TagInputTrait;
    use TooltipAttributeTrait;

    protected string|int $checkedValue = '1';
    protected string|int|null $uncheckedValue = null;

    protected bool $hasCheckedValue = false;
    protected bool $hasUncheckedValue = false;

    public function checkedValue(string|int $checkedValue): static
    {
        $this->checkedValue = $checkedValue;
        $this->hasCheckedValue = true;
        return $this;
    }

    /** `null` renders no hidden input, so an unticked box posts nothing. */
    public function uncheckedValue(string|int|null $uncheckedValue): static
    {
        $this->uncheckedValue = $uncheckedValue;
        $this->hasUncheckedValue = true;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        if ((!$this->hasCheckedValue || !$this->hasUncheckedValue) && $this->model && $this->property) {
            foreach ($this->model->getActiveValidators($this->property) as $validator) {
                if ($validator instanceof BooleanValidator) {
                    // From the rule, or the field posts what that same rule refuses.
                    if (!$this->hasCheckedValue) {
                        $this->checkedValue = $this->getCheckboxValue($validator->trueValue) ?? '1';
                    }

                    if (!$this->hasUncheckedValue) {
                        $this->uncheckedValue = $this->getCheckboxValue($validator->falseValue);
                    }

                    break;
                }
            }
        }

        parent::configure();
    }

    /** {@see BooleanValidator}'s values are untyped, and one a checkbox cannot carry has no place in it. */
    protected function getCheckboxValue(mixed $value): string|int|null
    {
        return is_string($value) || is_int($value) ? $value : null;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $this->setDescribedBy();

        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->addClass('form-checkbox-row')
            ->content(
                Div::make()
                    ->addClass('checkbox')
                    ->content($this->getInput()),
                Div::make()
                    ->content(
                        $this->getLabel(),
                        $this->getError(),
                        $this->getHint()
                    )
            );
    }

    protected function getInput(): string|Stringable
    {
        $this->attributes['type'] ??= 'checkbox';

        // What the record holds decides the tick, what the box posts is the checked value — two different
        // things in the one `value` attribute, so the first is read before the second overwrites it.
        $value = $this->attributes['value'] ?? $this->model->{$this->property} ?? '';
        $this->attributes['value'] = $this->checkedValue;

        $input = Checkbox::make()
            ->attributes($this->attributes)
            ->addClass('input')
            ->checked((string)$value === (string)$this->checkedValue);

        if (null !== $this->uncheckedValue) {
            $uncheckedInput = Input::make()
                ->attributes([
                    'type' => 'hidden',
                    'name' => $this->attributes['name'],
                    'value' => $this->uncheckedValue,
                ]);

            return $uncheckedInput . $input;
        }

        return $input;
    }
}
