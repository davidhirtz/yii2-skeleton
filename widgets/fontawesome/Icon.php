<?php

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * FontAwesome 5 Icon class.
 */
class Icon
{
    /**
     * @var array
     */
    private $options;

    /**
     * CSS class prefix
     * @var string
     */
    public static $cssClassPrefix = 'fa-';

    /**
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options = [])
    {
        Html::addCssClass($options, static::$cssClassPrefix . $name);
        $this->options = $options;
    }

    /**
     * Creates an Icon component.
     *
     * @param string $name
     * @param array $options
     * @return Icon
     */
    public static function tag($name, $options = [])
    {
        $method = ArrayHelper::remove($options, 'type', 'solid');
        return static::$method($name, $options);
    }

    /**
     * @param string $name
     * @param array $options
     * @return Icon
     */
    public static function solid($name, $options = [])
    {
        Html::addCssClass($options, 'fas');
        return new static($name, $options);
    }

    /**
     * @param string $name
     * @param array $options
     * @return Icon
     */
    public static function brand($name, $options = [])
    {
        Html::addCssClass($options, 'fab');
        return new static($name, $options);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $options = $this->options;
        $tag = ArrayHelper::remove($options, 'tag', 'i');

        return Html::tag($tag, null, $options);
    }

    /**
     * @return Icon
     */
    public function inverse()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'inverse');
    }

    /**
     * @return Icon
     */
    public function spin()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'spin');
    }

    /**
     * @return Icon
     */
    public function pulse()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pulse');
    }

    /**
     * @return Icon
     */
    public function fixedWidth()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'fw');
    }

    /**
     * @return Icon
     */
    public function li()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'li');
    }

    /**
     * @return Icon
     */
    public function border()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'border');
    }

    /**
     * @return Icon
     */
    public function pullLeft()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pull-left');
    }

    /**
     * @return Icon
     */
    public function pullRight()
    {
        return $this->addCssClass(static::$cssClassPrefix . 'pull-right');
    }

    /**
     * @param string $value
     * @return Icon
     */
    public function size($value)
    {
        return $this->addCssClass(static::$cssClassPrefix . $value);
    }

    /**
     * @param string $value
     * @return Icon
     */
    public function rotate($value)
    {
        return $this->addCssClass(static::$cssClassPrefix . 'rotate-' . $value);
    }

    /**
     * @param string $value
     * @return Icon
     */
    public function flip($value)
    {
        return $this->addCssClass(static::$cssClassPrefix . 'flip-' . $value);
    }

    /**
     * @param string $class
     * @return Icon
     */
    public function addCssClass($class)
    {
        Html::addCssClass($this->options, $class);
        return $this;
    }
}
