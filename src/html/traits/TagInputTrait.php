<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagInputTrait
{
    public function disabled(bool $disabled = true): static
    {
        return $this->attribute('disabled', $disabled);
    }

    public function form(string $formId): static
    {
        return $this->attribute('form', $formId);
    }

    public function name(?string $name): static
    {
        return $this->attribute('name', $name);
    }

    public function required(bool $required = true): static
    {
        return $this->attribute('required', $required ? '' : null);
    }

    public function type(?string $type): static
    {
        return $this->attribute('type', $type);
    }

    public function value(mixed $value): static
    {
        return $this->attribute('value', $value);
    }
}
