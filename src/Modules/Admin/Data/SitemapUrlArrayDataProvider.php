<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Data;

use DateTimeInterface;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Sitemap\SitemapInterface;
use Override;
use yii\data\ArrayDataProvider;

/**
 * @phpstan-type SitemapUrl array{loc: string, lastmod: DateTimeInterface|string|null, imageCount: int}
 * @property list<SitemapUrl> $allModels
 */
class SitemapUrlArrayDataProvider extends ArrayDataProvider
{
    public SitemapInterface $sitemap;

    /**
     * The sitemap's page, as `sitemap.xml` splits it under {@see \Hirtz\Skeleton\Sitemap\Sitemap::$useSitemapIndex}.
     */
    public int $offset = 0;

    #[Override]
    public function init(): void
    {
        $this->allModels = array_map($this->normalizeUrl(...), $this->sitemap->generateUrls($this->offset));
        $this->setPagination(['defaultPageSize' => 100]);

        parent::init();
    }

    public function hasImages(): bool
    {
        foreach ($this->allModels as $url) {
            if ($url['imageCount'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed>|string $url
     * @return SitemapUrl
     */
    protected function normalizeUrl(array|string $url): array
    {
        /** @var array<int|string, mixed>|string $loc */
        $loc = is_array($url) ? $url['loc'] : $url;
        $lastmod = is_array($url) ? ($url['lastmod'] ?? null) : null;
        $images = is_array($url) ? ($url['images'] ?? null) : null;

        return [
            'loc' => Url::to($loc, true),
            'lastmod' => $lastmod instanceof DateTimeInterface || is_string($lastmod) && $lastmod !== '' ? $lastmod : null,
            'imageCount' => is_array($images) ? count($images) : 0,
        ];
    }
}
