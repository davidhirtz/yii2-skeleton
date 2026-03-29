<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagTooltipAttributeTrait;
use Override;
use Stringable;

class Icon extends Widget
{
    use TagAttributesTrait;
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

    protected function configure(): void
    {
        $this->addClass(match ($this->collection) {
            self::ICON_COLLECTION_BRAND => "fab fa-$this->name",
            self::ICON_COLLECTION_FLAG => "i18n-icon $this->name",
            default => "fas fa-$this->name",
        });

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Span::make()->attributes($this->attributes);
    }
}
