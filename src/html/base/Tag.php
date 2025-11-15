<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use Stringable;

abstract class Tag implements Stringable
{
    protected array $attributes = [];
    private static int $counter = 0;

    final public function __construct()
    {
    }

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

    final public function getId(): string
    {
        return $this->attributes['id'] ??= 'i' . ++self::$counter;
    }

    final protected function renderAttributes(): string
    {
        $this->prepareAttributes();
        return Html::renderTagAttributes($this->attributes);
    }

    protected function prepareAttributes(): void
    {
    }

    final public function render(): string
    {
        return $this->before() . $this->renderTag() . $this->after();
    }

    protected function after(): string
    {
        return '';
    }

    protected function before(): string
    {
        return '';
    }

    protected function renderTag(): string
    {
        return '<' . $this->getTagName() . $this->renderAttributes() . '>' . $this->renderContent() . '</' . $this->getTagName() . '>';
    }

    protected function renderContent(): string
    {
        return '';
    }

    final public static function make(): static
    {
        return new static();
    }

    final public function __toString(): string
    {
        return $this->render();
    }

    protected function getTagName(): string
    {
        return 'div';
    }
}
