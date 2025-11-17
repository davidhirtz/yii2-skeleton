<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use Stringable;

abstract class Tag implements Stringable
{
    use TagAttributesTrait;
    use TagIdTrait;

    final public function __construct()
    {
    }

    final public function render(): string
    {
        return $this->before() . $this->getTag() . $this->after();
    }

    protected function before(): string
    {
        return '';
    }

    protected function after(): string
    {
        return '';
    }

    protected function getTag(): string
    {
        return '<' . $this->getTagName() . $this->getAttributes() . '>' . $this->renderContent() . '</' . $this->getTagName() . '>';
    }

    final protected function getAttributes(): string
    {
        $this->prepareAttributes();
        return Html::renderTagAttributes($this->attributes);
    }

    protected function prepareAttributes(): void
    {
    }

    protected function renderContent(): string|Stringable
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
