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

    public const string ICON_COLLECTION_BRAND = 'brand';
    public const string ICON_COLLECTION_FLAG = 'flag';

    private string $name;
    private ?string $collection = null;

    public function name(string $name): static
    {
        $this->name = $name;

        if (str_contains($name, ':')) {
            [$this->collection, $this->name] = explode(':', $name, 2);
        }

        return $this;
    }

    public function collection(string $collection): static
    {
        $this->collection = $collection;
        return $this;
    }

    protected function prepareAttributes(): void
    {
        $this->addClass(match ($this->collection) {
            self::ICON_COLLECTION_BRAND => "fab fa-$this->name",
            self::ICON_COLLECTION_FLAG => "i18n-icon $this->name",
            default => "fas fa-$this->name",
        });
    }

    #[Override]
    protected function getTagName(): string
    {
        return 'i';
    }
}
