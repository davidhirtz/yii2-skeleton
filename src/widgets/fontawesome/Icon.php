<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use Stringable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

final class Icon implements Stringable
{
    public static string $cssClassPrefix = 'fa-';

    public function __construct(string $name, private array $options = [])
    {
        Html::addCssClass($options, static::$cssClassPrefix . $name);
        $this->options = $options;
    }
    
    public static function tag(string $name, array $options = []): static
    {
        $method = ArrayHelper::remove($options, 'type', 'solid');
        return static::$method($name, $options);
    }

    public static function solid(string $name, array $options = []): static
    {
        Html::addCssClass($options, 'fas');
        return new static($name, $options);
    }

    public static function brand(string $name, array $options = []): static
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

    public function inverse(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'inverse');
    }

    public function spin(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'spin');
    }

    public function pulse(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pulse');
    }

    public function fixedWidth(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'fw');
    }

    public function li(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'li');
    }

    public function border(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'border');
    }

    public function pullLeft(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pull-left');
    }

    public function pullRight(): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pull-right');
    }

    public function size(string $value): static
    {
        return $this->addCssClass(static::$cssClassPrefix . $value);
    }

    public function rotate(string $value): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'rotate-' . $value);
    }

    public function flip(string $value): static
    {
        return $this->addCssClass(static::$cssClassPrefix . 'flip-' . $value);
    }

    public function addCssClass(string $class): static
    {
        Html::addCssClass($this->options, $class);
        return $this;
    }
}
