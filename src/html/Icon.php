<?php

namespace davidhirtz\yii2\skeleton\html;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\NormalTag;

class Icon extends NormalTag
{
    private string $icon = '';
    private string $collection = 'fas';

    public static function solid(string $name, array $attributes = []): self
    {
        $new = self::tag();
        $new->icon = $name;
        $new->attributes = $attributes;

        return $new;
    }

    public static function brand(string $name, array $attributes = []): self
    {
        $new = self::tag();
        $new->attributes = $attributes;
        $new->collection = 'fab';
        $new->icon = $name;

        return $new;
    }

    public function icon(string $icon): self
    {
        $new = clone $this;
        $new->icon = $icon;
        return $new;
    }

    public function collection(string $collection): self
    {
        $new = clone $this;
        $new->collection = $collection;
        return $new;
    }

    protected function prepareAttributes(): void
    {
        Html::addCssClass($this->attributes, "$this->collection fa-$this->icon");
        parent::prepareAttributes();
    }

    protected function generateContent(): string
    {
        return '';
    }

    protected function getName(): string
    {
        return 'i';
    }
}
