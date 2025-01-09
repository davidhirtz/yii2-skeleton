<?php

namespace davidhirtz\yii2\skeleton\html\traits;

trait TagInputTrait
{
    public function disabled(bool $disabled = true): static
    {
        return $this->attribute('disabled', $disabled);
    }

    public function name(?string $name): static
    {
        return $this->attribute('name', $name);
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
