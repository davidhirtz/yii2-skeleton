<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
use Override;
use Stringable;

class CheckboxField extends Field
{
    use TagInputTrait;

    protected string|int $checkedValue = '1';
    protected string|int|null $uncheckedValue = null;

    public function checkedValue(string|int $checkedValue): static
    {
        $this->checkedValue = $checkedValue;
        $this->attributes['value'] = $checkedValue;
        return $this;
    }

    public function uncheckedValue(string|int|null $uncheckedValue): static
    {
        $this->uncheckedValue = $uncheckedValue;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return FormRow::make()
            ->attributes($this->rowAttributes)
            ->addClass('form-checkbox-row')
            ->content(
                Div::make()
                    ->content($this->getInput()),
                Div::make()
                    ->content(
                        $this->getLabel(),
                        $this->getError(),
                        $this->getHint()));
    }

    protected function getInput(): string|Stringable
    {
        $this->attributes['type'] ??= 'checkbox';
        $value = $this->attributes['value'] ?? $this->model->{$this->property} ?? '';
        $this->attributes['value'] = $this->checkedValue;
        $this->attributes['checked'] = ((string)$value == (string)$this->checkedValue);

        $input = Input::make()
            ->attributes($this->attributes)
            ->addClass('input');

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
