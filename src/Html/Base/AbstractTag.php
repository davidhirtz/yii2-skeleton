<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Html\Base;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Stringable;

abstract class AbstractTag implements Stringable
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

    final protected function getAttributes(): string
    {
        return Html::renderTagAttributes($this->attributes);
    }

    final public static function make(): static
    {
        return new static();
    }

    final public function __toString(): string
    {
        return $this->render();
    }

    abstract protected function getTag(): string;
    abstract protected function getTagName(): string;
}
