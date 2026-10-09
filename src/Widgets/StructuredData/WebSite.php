<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\StructuredData;

use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Helpers\Url;
use Override;
use Yii;

class WebSite extends Thing
{
    final public const string FRAGMENT = 'website';

    protected ?string $type = 'WebSite';
    protected ?string $name = null;
    protected ?string $url = null;
    protected ?string $publisher = null;

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

    public function publisher(?string $publisher): static
    {
        $this->publisher = $publisher;
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
        $this->name ??= Yii::$app->name;
        $this->url ??= Url::home(true);

        parent::configure();
    }

    #[Override]
    protected function getProperties(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'inLanguage' => Yii::$app->language,
            'publisher' => $this->publisher !== null ? ['@id' => $this->publisher] : null,
            ...parent::getProperties(),
        ];
    }
}
