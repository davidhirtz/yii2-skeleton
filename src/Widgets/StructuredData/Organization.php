<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Helpers\Url;
use Override;

class Organization extends Thing
{
    final public const string FRAGMENT = 'organization';

    protected ?string $type = 'Organization';
    protected ?string $name = null;
    protected ?string $url = null;
    protected ?string $logo = null;

    /**
     * @var list<string>
     */
    protected array $sameAs = [];

    public function name(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function url(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function logo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * @param list<string> $sameAs
     */
    public function sameAs(array $sameAs): static
    {
        $this->sameAs = $sameAs;
        return $this;
    }

    public static function getDefaultId(): string
    {
        return StructuredData::id(Url::home(true), self::FRAGMENT);
    }

    #[Override]
    protected function configure(): void
    {
        $this->id ??= static::getDefaultId();
        $this->url ??= Url::home(true);

        parent::configure();
    }

    public function isVisible(): bool
    {
        return $this->name !== null && $this->name !== '' && parent::isVisible();
    }

    #[Override]
    protected function getProperties(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'logo' => $this->logo !== null ? StructuredData::image($this->logo) : null,
            'sameAs' => $this->sameAs ?: null,
            ...parent::getProperties(),
        ];
    }
}
