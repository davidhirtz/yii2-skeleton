<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Sitemap;

use DateTimeInterface;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\FileHelper;
use Yii;

class UrlSitemap extends AbstractSitemap
{
    /**
     * @var array containing sitemap URLs. A URL can be set as route or relative URL. If additional information such
     * as priority or last modified should be added, an array with the url as "loc" value can be used.
     */
    public array $urls = [];

    /**
     * @var array containing the static views. Array keys "alias" for the view path and "route" string for URL manager
     * are required. Optional "params" for additional route params, "paramName" for the view param and "exclude" for
     * files that should not be included.
     */
    public array $views = [];

    private ?array $generatedUrls = null;

    public function generateUrls(?int $offset = null): array
    {
        $urls = $this->getUrls();

        return $offset === null
            ? $urls
            : array_slice($urls, $offset * $this->getMaxUrlCount(), $this->getMaxUrlCount());
    }

    public function getPageCount(): int
    {
        return (int)ceil(count($this->getUrls()) / $this->getMaxUrlCount());
    }

    public function getLastModified(int $offset = 0): ?string
    {
        $lastModified = null;

        foreach ($this->generateUrls($offset) as $url) {
            $value = is_array($url) ? ($url['lastmod'] ?? null) : null;
            $value = $value instanceof DateTimeInterface ? $value->format(DATE_W3C) : $value;

            if ($value !== null && ($lastModified === null || $value > $lastModified)) {
                $lastModified = (string)$value;
            }
        }

        return $lastModified;
    }

    public function getUrls(): array
    {
        return $this->generatedUrls ??= [...$this->urls, ...($this->views ? $this->generateFileUrls() : [])];
    }

    /**
     * Generates sitemap URLs from view files.
     */
    public function generateFileUrls(): array
    {
        $manager = Yii::$app->getUrlManager();
        $defaultLanguages = $manager->i18nUrl ? array_keys((array)$manager->languages) : [null];
        $urls = [];

        foreach ($this->views as $view) {
            if (!isset($view['alias'], $view['route'])) {
                continue;
            }

            $languages = $view['languages'] ?? $defaultLanguages;
            $paramName = $view['paramName'] ?? 'view';
            $defaultView = $view['defaultView'] ?? 'index';
            $params = $view['params'] ?? [];

            $options = ArrayHelper::merge($view['options'] ?? [], [
                'except' => ['_*', 'error.php'],
                'recursive' => false,
            ]);

            foreach (FileHelper::findFiles(Yii::getAlias($view['alias']), $options) as $file) {
                $name = pathinfo((string)$file, PATHINFO_FILENAME);

                foreach ($languages as $language) {
                    $route = [$view['route'], ...$params];

                    // `false` would collide with the route's own key, which is `0`
                    if ($paramName !== false && $name !== $defaultView) {
                        $route[$paramName] = $name;
                    }

                    if ($language !== null) {
                        $route['language'] = $language;
                    }

                    $urls[] = [
                        'loc' => $route,
                        'lastmod' => date(DATE_W3C, (int)filemtime($file)),
                    ];
                }
            }
        }

        return $urls;
    }
}
