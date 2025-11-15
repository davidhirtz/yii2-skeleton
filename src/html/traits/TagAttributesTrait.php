<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\traits;

use davidhirtz\yii2\skeleton\helpers\Html;

trait TagAttributesTrait
{
    public array $attributes = [];

    final public function addAttributes(array $attributes): static
    {
        $this->attributes = [...$this->attributes, ...$attributes];
        return $this;
    }

    final public function attribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    final public function unionAttributes(array $attributes): static
    {
        $this->attributes += $attributes;
        return $this;
    }

    final public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    final public function addClass(string|null ...$class): static
    {
        if ($class) {
            Html::addCssClass($this->attributes, $class);
        }

        return $this;
    }

    final public function class(string|null ...$class): static
    {
        $this->attributes['class'] = $class;
        return $this;
    }

    final public function addStyle(array|string $style, bool $overwrite = true): static
    {
        Html::addCssStyle($this->attributes, $style, $overwrite);
        return $this;
    }

    final public function removeStyle(string|array $properties): static
    {
        Html::removeCssStyle($this->attributes, $properties);
        return $this;
    }

    final protected function renderAttributes(): string
    {
        $this->prepareAttributes();
        return Html::renderTagAttributes($this->attributes);
    }

    protected function prepareAttributes(): void
    {
    }
}
