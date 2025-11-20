<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\widgets\forms\FormRow;
use Stringable;

class CheckboxField extends Field
{
    use TagInputTrait;

    protected string|int|null $uncheckedValue = null;

    public function unchecked(string|int|null $uncheckedValue): static
    {
        $this->uncheckedValue = $uncheckedValue;
        return $this;
    }

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

    public function getInput(): string|Stringable
    {
        $this->attributes['type'] ??= 'checkbox';
        $this->attributes['value'] ??= $this->model?->{$this->property};

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
