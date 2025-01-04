<?php

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TooltipAttributeTrait;
use Yii;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\Tag;

class Icon extends Tag
{
    use TooltipAttributeTrait;

    public static function tag(string $name, array $attributes = []): self
    {
        $class = str_starts_with('brand:', $name) ? 'fab fa-' . substr($name, 6) : "fas fa-$name";
        Html::addCssClass($attributes, $class);

        $instance = Yii::$container->get(self::class);
        $instance->attributes = $attributes;

        return $instance;
    }

    protected function getName(): string
    {
        return 'i';
    }

    protected function renderTag(): string
    {
        return '<' . $this->getName() . $this->renderAttributes() . '></' . $this->getName() . '>';
    }
}
