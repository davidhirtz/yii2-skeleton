<?php

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
use Yii;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Base\Tag;

class Icon extends Tag
{
    use TagTooltipAttributeTrait;

    private string $name;
    private ?string $collection = null;

    public static function tag(string $name, array $attributes = []): self
    {
        $instance = Yii::$container->get(self::class);
        $instance->attributes = $attributes;
        $instance->name = $name;

        if (str_contains($name, ':')) {
            [$instance->collection, $instance->name] = explode(':', $name, 2);
        }

        return $instance;
    }

    public function collection(string $collection): self
    {
        $new = clone $this;
        $new->collection = $collection;
        return $new;
    }

    protected function prepareAttributes(): void
    {
        Html::addCssClass($this->attributes, match ($this->collection) {
            'brand' => "fab fa-$this->name",
            'flag' => "i18n-icon $this->name",
            default => "fas fa-$this->name",
        });
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
