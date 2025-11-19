<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\widgets\forms\ActiveForm;
use Stringable;

class InputField extends Field
{
    use TagInputTrait;

    public function form(ActiveForm $form): static
    {
        parent::form($form);

        $this->attributes['type'] ??= 'text';
        $this->attributes['value'] ??= $this->model->{$this->property};

        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return 'hidden' === $this->attributes['type']
            ? $this->getInput()
            : parent::renderContent();
    }

    public function getInput(): string|Stringable
    {
        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }
}
