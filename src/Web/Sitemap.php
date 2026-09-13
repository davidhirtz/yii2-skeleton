<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Behaviors\SitemapBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\Interfaces\SitemapInterface;
use Yii;
use yii\base\Component;
use yii\caching\Cache;
use yii\caching\Dependency;

class Sitemap extends Component
{
    public Cache|string|null $cache = 'cache';

    public int $duration = 86400;

    public array|Dependency|null $dependency = null;

    /**
     * @var callable|string[]|string|null the list of factors that would cause the variation of the sitemap being cached.
     * Each factor is a string representing a variation (e.g., the language, a GET parameter). This can be also
     * callable to configure it during application setup:
     *
     * `
     * 'variations' => function () {
     *       return [Yii::$app->language];
     *   },
     * `
     */
    public mixed $variations = null;

    /**
     * @var bool whether sitemaps should be split into separate sitemap files. This is necessary for a sitemaps which
     * would exceed 50 MB or 50.000 URLs.
     */
    public bool $useSitemapIndex = false;

    /**
     * @var int the maximum number of sitemap URLs per sitemap. This is used to split URLs into separate sitemaps when
     * `useSitemapIndex` is set to true.
     */
    public int $maxUrlCount = 50000;

    /**
     * @var array containing the static views. Array keys "alias" for the view path and "route" string for URL manager
     * are required. Optional "params" for additional route params, "paramName" for the view param and "exclude" for
     * files that should not be included.
     */
    public array $views = [];

    /**
     * @var array<array-key, class-string<ActiveRecord&SitemapInterface>|array<string, mixed>> the class definitions of all
     * {@see ActiveRecord} which should be used to create sitemap URLs via the {@see SitemapBehavior} behavior. A
     * definition may carry a `behaviors` key, which is attached to the model rather than passed to its constructor.
     * If `useSitemapIndex` is set to true, the key can optionally be set to a string which is then used in the
     * sitemap index url generation.
     */
    public array $models = [];

    /**
     * @var array<array-key, ActiveRecord&SitemapInterface> the models of {@see static::$models}, instantiated by
     * {@see init()}.
     */
    private array $sitemapModels = [];

    /**
     * @var array containing additional sitemap URLs. Urls can be set as route or relative URL. If additional
     * information such as priority or last modified should be added, an array with the url as "loc" value can be
     * used.
     */
    public array $urls = [];

    public function init(): void
    {
        foreach ($this->models as $key => $config) {
            // the behaviors belong to the configuration, not to the object: `Yii::createObject()` has no property
            // to assign them to, and reading them back off the model returns the ones it already attached
            $behaviors = is_array($config) ? ArrayHelper::remove($config, 'behaviors', []) : [];

            if (isset($behaviors['sitemap']) && is_array($behaviors['sitemap'])) {
                $behaviors['sitemap']['class'] ??= SitemapBehavior::class;
            }

            $model = Yii::createObject($config);

            if ($behaviors) {
                $model->attachBehaviors($behaviors);
            }

            $this->sitemapModels[$key] = $model;
        }

        if (is_callable($this->variations)) {
            $this->variations = call_user_func($this->variations);
        }

        parent::init();
    }

    /**
     * Generates sitemap URLs from default URLs, views, and models. Depending on whether `useSitemapIndex` is `true`,
     * this method either generates all URLs for a single sitemap or uses the given `key` (corresponding to the `model`
     * array key) and `offset` to create only the requested URLs.
     */
    public function generateUrls(string|int|null $key = null, int $offset = 0): array
    {
        if (!$this->useSitemapIndex) {
            $urls = $this->getUrlsInternal();

            foreach ($this->sitemapModels as $model) {
                $urls = [...$urls, ...$model->generateSitemapUrls()];
            }

            return $urls;
        }

        if ($key === 'urls') {
            return array_slice($this->getUrlsInternal(), $offset * $this->maxUrlCount, $this->maxUrlCount);
        }

        $model = $this->sitemapModels[$key] ?? null;

        return $model?->generateSitemapUrls($offset) ?? [];
    }

    /**
     * Generates an index of sitemap.xml URLs.
     */
    public function generateIndexUrls(): array
    {
        $urls = $this->getUrlsInternal();
        $sitemaps = [];
        $offset = 0;

        // Split default urls into separate sitemap url sets.
        while ($urlset = array_splice($urls, 0, $this->maxUrlCount)) {
            $sitemaps[] = [
                'loc' => ['sitemap/index', 'key' => 'urls', 'offset' => $offset++],
                'lastmod' => $this->getMaxLastMod($urlset),
            ];
        }

        // A model sitemap has no `lastmod`: nothing asks the model for one, and `$urlset` is spent by the loop above
        foreach ($this->sitemapModels as $key => $model) {
            $total = ceil($model->getSitemapUrlCount() / $this->maxUrlCount);

            for ($offset = 0; $offset < $total; $offset++) {
                $sitemaps[] = [
                    'loc' => ['sitemap/index', 'key' => $key, 'offset' => $offset],
                ];
            }
        }

        return $sitemaps;
    }

    /**
     * Generates site maps from view files.
     *
     * Required config parameters are "alias" and "route", optional "languages", "paramName", "defaultView" and
     * "options" for FileHelper::findFiles.
     */
    public function generateFileUrls(): array
    {
        $manager = Yii::$app->getUrlManager();
        $defaultLanguages = $manager->i18nUrl ? array_keys($manager->languages) : [null];
        $urls = [];

        foreach ($this->views as $view) {
            $languages = $view['languages'] ?? $defaultLanguages;
            $paramName = $view['paramName'] ?? 'view';
            $defaultView = $view['defaultView'] ?? 'index';
            $params = $view['params'] ?? [];

            $options = ArrayHelper::merge($view['options'] ?? [], [
                'except' => ['_*', 'error.php'],
                'recursive' => false,
            ]);

            if (isset($view['alias'], $view['route'])) {
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
                            'lastmod' => date(DATE_W3C, filectime($file)),
                        ];
                    }
                }
            }
        }

        return $urls;
    }

    private function getUrlsInternal(): array
    {
        return $this->views ? [...$this->urls, ...$this->generateFileUrls()] : $this->urls;
    }

    private function getMaxLastMod(array $urls): ?string
    {
        $lastMod = null;

        foreach ($urls as $url) {
            if (($url['lastmod'] ?? null) > $lastMod) {
                $lastMod = $url['lastmod'];
            }
        }

        return $lastMod;
    }
}
