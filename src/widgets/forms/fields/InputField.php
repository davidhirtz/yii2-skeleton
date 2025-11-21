<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagIconTrait;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagPlaceholderTrait;
use davidhirtz\yii2\skeleton\widgets\forms\InputGroup;
use davidhirtz\yii2\skeleton\widgets\forms\traits\InputGroupTrait;
use Stringable;
use yii\validators\StringValidator;

class InputField extends Field
{
    use InputGroupTrait;
    use TagInputTrait;
    use TagPlaceholderTrait;

    protected function renderContent(): string|Stringable
    {
        if ('hidden' === ($this->attributes['type'] ?? null)) {
            return $this->getInput();
        }

        return parent::renderContent();
    }

    protected function getInput(): string|Stringable
    {
        $this->attributes['type'] ??= 'text';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        if (array_key_exists('placeholder', $this->attributes)) {
            $this->attributes['placeholder'] ??= $this->model?->getAttributeLabel($this->property);
        }

        foreach ($this->model?->getActiveValidators($this->property) ?? [] as $validator) {
            if ($validator instanceof StringValidator) {
                $this->attributes['maxlength'] ??= $validator->max ?? $validator->length;
                $this->attributes['minlength'] ??= $validator->min ?? $validator->length;
            }
        }

        $input = Input::make()
            ->attributes($this->attributes)
            ->addClass('input');

        return $this->append || $this->prepend
            ? InputGroup::make()
                ->append(...$this->append)
                ->prepend(...$this->prepend)
                ->content($input)
            : $input;
    }
}
