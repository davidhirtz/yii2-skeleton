<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use Stringable;
use yii\validators\StringValidator;

class InputField extends Field
{
    use TagInputTrait;

    public function form(ActiveForm $form): static
    {
        parent::form($form);


        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        $this->attributes['type'] ??= 'text';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        if ('hidden' === $this->attributes['type']) {
            return $this->getInput();
        }

        foreach ($this->model?->getActiveValidators($this->property) ?? [] as $validator) {
            if ($validator instanceof StringValidator) {
                $this->attributes['maxlength'] ??= $validator->max;
                $this->attributes['minlength'] ??= $validator->min;
            }
        }

        return parent::renderContent();
    }

    public function getInput(): string|Stringable
    {
        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }
}
