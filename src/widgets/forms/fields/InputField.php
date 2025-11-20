<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use Stringable;
use yii\validators\StringValidator;

class InputField extends Field
{
    use TagInputTrait;

    protected function renderContent(): string|Stringable
    {
        if ('hidden' === ($this->attributes['type'] ?? null)) {
            return $this->getInput();
        }

        return parent::renderContent();
    }

    public function getInput(): string|Stringable
    {
        $this->attributes['type'] ??= 'text';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        foreach ($this->model?->getActiveValidators($this->property) ?? [] as $validator) {
            if ($validator instanceof StringValidator) {
                $this->attributes['maxlength'] ??= $validator->max ?? $validator->length;
                $this->attributes['minlength'] ??= $validator->min?? $validator->length;
            }
        }

        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }
}
