<?php

namespace davidhirtz\yii2\skeleton\html;

use Yii;
use Yiisoft\Html\Tag\Base\Tag;

class BaseTag extends Tag
{
    public static function tag(): static
    {
        /** @var static $self */
        $self = Yii::$container->get(static::class);
        return $self;
    }

    protected function renderTag(): string
    {
        return $this->open() . $this->generateContent() . $this->close();
    }

    public function open(): string
    {
        return '<' . $this->getName() . $this->renderAttributes() . '>' . $this->prepend();
    }

    protected function prepend(): string
    {
        return '';
    }

    public function close(): string
    {
        return '</' . $this->getName() . '>';
    }

    protected function generateContent(): string
    {
        return '';
    }

    protected function getName(): string
    {
        return 'div';
    }
}
