<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use Stringable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

final class Icon implements Stringable
{
    public static string $cssClassPrefix = 'fa-';

    public function __construct(string $name, private array $options = [])
    {
        Html::addCssClass($options, self::$cssClassPrefix . $name);
        $this->options = $options;
    }
    
    public static function tag(string $name, array $options = []): self
    {
        $method = ArrayHelper::remove($options, 'type', 'solid');
        return self::$method($name, $options);
    }

    public static function solid(string $name, array $options = []): self
    {
        Html::addCssClass($options, 'fas');
        return new static($name, $options);
    }

    public static function brand(string $name, array $options = []): self
    {
        Html::addCssClass($options, 'fab');
        return new static($name, $options);
    }

    public function __toString(): string
    {
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'i');

        return Html::tag($tag, '', $options);
    }

    public function inverse(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'inverse');
    }

    public function spin(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'spin');
    }

    public function pulse(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'pulse');
    }

    public function fixedWidth(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'fw');
    }

    public function li(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'li');
    }

    public function border(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'border');
    }

    public function pullLeft(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'pull-left');
    }

    public function pullRight(): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'pull-right');
    }

    public function size(string $value): self
    {
        return $this->addCssClass(self::$cssClassPrefix . $value);
    }

    public function rotate(string $value): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'rotate-' . $value);
    }

    public function flip(string $value): self
    {
        return $this->addCssClass(self::$cssClassPrefix . 'flip-' . $value);
    }

    public function addCssClass(string $class): self
    {
        Html::addCssClass($this->options, $class);
        return $this;
    }
}
