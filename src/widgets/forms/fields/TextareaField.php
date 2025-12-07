<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use davidhirtz\yii2\skeleton\html\Textarea;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\html\traits\TagPlaceholderTrait;
use Stringable;
use yii\validators\StringValidator;

class TextareaField extends Field
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    #[\Override]
    protected function configure(): void
    {
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

        parent::configure();
    }

    protected function getInput(): string|Stringable
    {
        return Textarea::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }
}
