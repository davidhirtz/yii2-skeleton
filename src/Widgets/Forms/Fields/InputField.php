<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Html\Input;
use Hirtz\Skeleton\Html\Traits\TagInputTrait;
use Hirtz\Skeleton\Html\Traits\TagPlaceholderTrait;
use Override;
use Stringable;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

class InputField extends Field
{
    use TagInputTrait;
    use TagPlaceholderTrait;

    #[Override]
    protected function configure(): void
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

            if ($validator instanceof NumberValidator) {
                $this->attributes['max'] ??= $validator->max;
                $this->attributes['min'] ??= $validator->min;
            }
        }

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        if (($this->attributes['type'] ?? null) === 'hidden') {
            return $this->getInput();
        }

        return parent::renderContent();
    }

    protected function getInput(): string|Stringable
    {
        return Input::make()
            ->attributes($this->attributes)
            ->addClass('input');
    }
}
