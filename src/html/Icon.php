<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\html;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\base\Tag;
use davidhirtz\yii2\skeleton\html\traits\TagTooltipAttributeTrait;
use Override;

class Icon extends Tag
{
    use TagTooltipAttributeTrait;

    private string $name;
    private ?string $collection = null;

    public static function tag(string $name, array $attributes = []): static
    {
        $instance = static::make();
        $instance->attributes = $attributes;
        $instance->name = $name;

        if (str_contains($name, ':')) {
            [$instance->collection, $instance->name] = explode(':', $name, 2);
        }

        return $instance;
    }

    public function collection(string $collection): static
    {
        $this->collection = $collection;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        Html::addCssClass($this->attributes, match ($this->collection) {
            'brand' => "fab fa-$this->name",
            'flag' => "i18n-icon $this->name",
            default => "fas fa-$this->name",
        });
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'i';
    }
}
