<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html\base;

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
