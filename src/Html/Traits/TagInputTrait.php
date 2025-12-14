<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Traits;

trait TagInputTrait
{
    public function autofocus(bool $autofocus = true): static
    {
        return $this->attribute('autofocus', $autofocus ? '' : null);
    }

    public function autocomplete(?string $autocomplete): static
    {
        return $this->attribute('autocomplete', $autocomplete);
    }

    public function disabled(bool $disabled = true): static
    {
        return $this->attribute('disabled', $disabled ? '' : null);
    }

    public function name(?string $name): static
    {
        return $this->attribute('name', $name);
    }

    public function pattern(?string $pattern): static
    {
        return $this->attribute('pattern', $pattern);
    }

    public function readonly(bool $readonly = true): static
    {
        return $this->attribute('readonly', $readonly ? '' : null);
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
