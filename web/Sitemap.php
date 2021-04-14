<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\behaviors\SitemapBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\base\Component;
use yii\caching\Cache;
use yii\caching\Dependency;

/**
 * Class Sitemap
 * @package davidhirtz\yii2\skeleton\web
 */
class Sitemap extends Component
{
    /**
     * @var Cache|string
     */
    public $cache = 'cache';

    /**
     * @var int
     */
    public $duration = 86400;

    /**
     * @var array|Dependency
     */
    public $dependency;

    /**
     * @var string[]|string list of factors that would cause the variation of the sitemap being cached.
     * Each factor is a string representing a variation (e.g. the language, a GET parameter).
     */
    public $variations;

    /**
     * @var bool whether sitemaps should be split into separate sitemap files. This is needed for a sitemaps which would
     * exceed 50MB or 50.000 URLs.
     */
    public $useSitemapIndex = false;

    /**
     * @var int the maximum number of sitemap URLs per sitemap. This is used to split URLs into separate sitemaps when
     * `useSitemapIndex` is set to true.
     */
    public $maxUrlCount = 3;

    /**
     * @var array containing the static views. Array keys "alias" for view path and "route" string for URL manager are
     * required. Optional "params" for additional route params, "paramName" for the view param and "exclude" for files
     * that should not be included.
     */
    public $views = [];

    /**
     * @var SitemapBehavior[]|array containing the class definitions of all {@link ActiveRecord} which should be used to
     * create sitemap URLs via the {@link SitemapBehavior} behavior. If `useSitemapIndex` is set to true, the key can
     * optionally be set to a string which is then used in the sitemap index url generation.
     */
    public $models = [];

    /**
     * @var array containing additional sitemap URLs. Urls can be set as route or relative URL. If additional information
     * such as priority or last modified should be added, an array with the url as "loc" value can be used.
     */
    public $urls = [];

    /**
     * @inheritDoc
     */
    public function init()
    {
        foreach ($this->models as &$model) {
            $model = Yii::createObject($model);

            if ($behaviors = ($modelName['behaviors'] ?? false)) {
                if (isset($behaviors['sitemap']) && empty($behaviors['sitemap']['class'])) {
                    $behaviors['sitemap']['class'] = SitemapBehavior::class;
                }

                $model->attachBehaviors($behaviors);
            }
        }

        parent::init();
    }

    /**
     * Generates sitemap URLs from default URLs, views and models. Depending on whether `useSitemapIndex` is `true` this
     * method either generates all URLs for a single sitemap or uses the given `key` (corresponding to the `model` array
     * key) and `offset` to create only the requested URLs.
     *
     * @param string|int $key
     * @param int $offset
     * @return array
     */
    public function generateUrls($key = null, $offset = 0)
    {
        if (!$this->useSitemapIndex) {
            $urls = $this->getUrlsInternal();

            foreach ($this->models as $key) {
                $urls = array_merge($urls, $key->generateSitemapUrls());
            }

            return $urls;
        }

        if ($key == 'urls') {
            return array_slice($this->getUrlsInternal(), $offset * $this->maxUrlCount, $this->maxUrlCount);
        }

        return ($model = $this->models[$key]) ? $model->generateSitemapUrls($offset) : [];
    }

    /**
     * Generates an index of sitemap file urls.
     */
    public function generateIndexUrls()
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

        foreach ($this->models as $key => $model) {
            $offset = 0;

            while ($urlset = $model->generateSitemapUrls($offset)) {
                $sitemaps[] = [
                    'loc' => ['sitemap/index', 'key' => $key, 'offset' => $offset++],
                    'lastmod' => $this->getMaxLastMod($urlset),
                ];
            }
        }

        return $sitemaps;
    }

    /**
     * Generates site maps from view files.
     *
     * Required config parameters are "alias" and "route", optional "languages", "paramName",
     * "defaultView" and "options" for FileHelper::findFiles.
     *
     * 'components' => [
     *    'sitemap' => [
     *        'views' => [
     *            [
     *                'alias' => '@app/views/site',
     *                'route' => 'site/static',
     *                'options' => [
     *                    'except' => ['hidden.php'],
     *                ],
     *            ],
     *        ],
     *    ],
     *
     * @return array
     */
    public function generateFileUrls()
    {
        $manager = Yii::$app->getUrlManager();
        $defaultLanguages = ($manager->hasI18nUrls() ? array_keys($manager->languages) : [null]);
        $urls = [];

        foreach ($this->views as $view) {
            $languages = $view['languages'] ?? $defaultLanguages;
            $paramName = $view['paramName'] ?? 'view';
            $defaultView = $view['defaultView'] ?? 'index';
            $params = [];

            $options = ArrayHelper::merge($view['options'] ?? [], [
                'except' => ['_*', 'error.php'],
                'recursive' => false,
            ]);

            if (isset($view['alias'], $view['route'])) {
                foreach (FileHelper::findFiles(Yii::getAlias($view['alias']), $options) as $file) {
                    $name = $paramName !== false ? pathinfo($file, PATHINFO_FILENAME) : null;

                    foreach ($languages as $language) {
                        $urls[] = [
                            'loc' => array_filter(array_merge([$view['route'], $paramName => $name !== $defaultView ? $name : null, 'language' => $language], $params)),
                            'lastmod' => date(DATE_W3C, filectime($file)),
                        ];
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * @return array
     */
    private function getUrlsInternal()
    {
        return $this->views ? array_merge($this->urls, $this->generateFileUrls()) : $this->urls;
    }

    /**
     * @param array $urls
     * @return string|null
     */
    private function getMaxLastMod($urls)
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