<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

use Override;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\caching\Dependency;

/**
 * The `sitemap` application component.
 */
class Sitemap extends Component
{
    /**
     * @var string the key of the sitemap holding {@see static::$urls} and {@see static::$views}.
     */
    final public const string URLS_KEY = 'urls';

    public Cache|string|null $cache = 'cache';

    public int $duration = 86400;

    /**
     * @var array<string, mixed>|Dependency|null
     */
    public array|Dependency|null $dependency = null;

    /**
     * @var callable|string[]|string|null the list of factors that would cause the variation of the sitemap being
     * cached. Each factor is a string representing a variation (e.g., the language, a GET parameter). This can be also
     * callable to configure it during application setup:
     *
     * `
     * 'variations' => function () {
     *       return [Yii::$app->language];
     *   },
     * `
     *
     * Read it through {@see getVariations()}, which is what normalizes a single factor into a list.
     */
    public mixed $variations = null;

    /**
     * @var bool whether sitemaps should be split into separate sitemap files. This is necessary for a sitemap which
     * would exceed 50 MB or 50.000 URLs.
     */
    public bool $useSitemapIndex = false;

    /**
     * @var int the maximum number of URLs per sitemap, used to split the URLs into separate sitemaps when
     * `useSitemapIndex` is set to true. A sitemap can override it via {@see AbstractSitemap::$maxUrlCount}.
     */
    public int $maxUrlCount = 50000;

    /**
     * @var array<string, mixed> containing the static views, see {@see UrlSitemap::$views}.
     */
    public array $views = [];

    /**
     * @var list<array<string, mixed>|string> containing additional sitemap URLs, see {@see UrlSitemap::$urls}.
     */
    public array $urls = [];

    /**
     * @var array<array-key, class-string<SitemapInterface>|array{class: class-string<SitemapInterface>, ...}> the class definitions of the
     * sitemaps. The key is what the sitemap index names the sitemap by, so it should be a stable string.
     */
    public array $sitemaps = [];

    /**
     * @var array<array-key, SitemapInterface>|null
     */
    private ?array $sitemapInstances = null;

    public static function getComponent(): self
    {
        /** @var self $sitemap */
        $sitemap = Yii::$app->get('sitemap');
        return $sitemap;
    }

    #[Override]
    public function init(): void
    {
        if (is_callable($this->variations)) {
            $this->variations = call_user_func($this->variations);
        }

        parent::init();
    }

    /**
     * @return list<string>
     */
    public function getVariations(): array
    {
        return array_values(array_map(strval(...), array_filter(
            (array)$this->variations,
            fn (mixed $variation): bool => $variation !== null && $variation !== '',
        )));
    }

    /**
     * @return array<array-key, SitemapInterface>
     */
    public function getSitemaps(): array
    {
        if ($this->sitemapInstances === null) {
            $this->sitemapInstances = [];

            if ($this->urls || $this->views) {
                $this->sitemapInstances[self::URLS_KEY] = $this->createSitemap([
                    'class' => UrlSitemap::class,
                    'urls' => $this->urls,
                    'views' => $this->views,
                ]);
            }

            foreach ($this->sitemaps as $key => $config) {
                if ($key === self::URLS_KEY) {
                    throw new InvalidConfigException('The sitemap key "' . self::URLS_KEY . '" is reserved.');
                }

                $this->sitemapInstances[$key] = $this->createSitemap($config);
            }
        }

        return $this->sitemapInstances;
    }

    public function getSitemap(string|int|null $key): ?SitemapInterface
    {
        return $key === null ? null : ($this->getSitemaps()[$key] ?? null);
    }

    /**
     * Generates the URLs of a single sitemap. Without `useSitemapIndex` there is only one, holding every URL, and
     * both `key` and `offset` are ignored.
     *
     * @return list<array<string, mixed>|string>
     */
    public function generateUrls(string|int|null $key = null, int $offset = 0): array
    {
        if (!$this->useSitemapIndex) {
            $urls = [];

            foreach ($this->getSitemaps() as $sitemap) {
                $urls = [...$urls, ...$sitemap->generateUrls()];
            }

            return $urls;
        }

        return $this->getSitemap($key)?->generateUrls(max(0, $offset)) ?? [];
    }

    /**
     * Generates an index of sitemap.xml URLs.
     *
     * @return list<array<string, mixed>|string>
     */
    public function generateIndexUrls(): array
    {
        $urls = [];

        foreach ($this->getSitemaps() as $key => $sitemap) {
            $pageCount = $sitemap->getPageCount();

            for ($offset = 0; $offset < $pageCount; $offset++) {
                $urls[] = array_filter([
                    'loc' => ['sitemap/index', 'key' => $key, 'offset' => $offset],
                    'lastmod' => $sitemap->getLastModified($offset),
                ], fn (mixed $value): bool => $value !== null);
            }
        }

        return $urls;
    }

    /**
     * @param class-string|array{class: class-string, ...} $config
     */
    private function createSitemap(string|array $config): SitemapInterface
    {
        $sitemap = Yii::createObject($config);

        if (!$sitemap instanceof SitemapInterface) {
            throw new InvalidConfigException('A sitemap must implement ' . SitemapInterface::class . '.');
        }

        if ($sitemap instanceof AbstractSitemap) {
            $sitemap->sitemap ??= $this;
        }

        return $sitemap;
    }
}
