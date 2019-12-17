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
     * @var string[]|string|callable
     */
    public $variations;

    /**
     * Static views.
     *
     * Needs array keys "alias" for view path and "route" string for URL manager.
     * Optional "params" for additional route params, "paramName" for the view param and
     * "exclude" for files that should not be included.
     *
     * @var array
     */
    public $views = [];

    /**
     * SitemapBehavior can be defined in model or config.
     * @var array
     */
    public $models = [];

    /**
     * @var array
     */
    public $urls = [];

    /**
     * @return array
     */
    public function generateUrls()
    {
        if ($this->views) {
            $this->generateFileUrls();
        }

        if ($this->models) {
            $this->generateModelUrls();
        }

        return $this->urls;
    }

    /**
     * Generates site maps from models.
     */
    private function generateFileUrls()
    {
        $manager = Yii::$app->getUrlManager();

        foreach ($this->views as $view) {
            $languages = $view['languages'] ?? ($manager->i18nUrl ? array_keys($manager->languages) : [null]);
            $paramName = $view['paramName'] ?? 'view';
            $defaultView = $view['defaultView'] ?? 'index';
            $params = [];

            $options = ArrayHelper::merge($view['options'] ?? [], [
                'except' => ['_*', 'error.php'],
                'recursive' => false,
            ]);

            foreach (FileHelper::findFiles(Yii::getAlias($view['alias']), $options) as $file) {
                $name = $paramName !== false ? pathinfo($file, PATHINFO_FILENAME) : null;
                foreach ($languages as $language) {
                    $this->urls[] = [
                        'loc' => array_filter(array_merge([$view['route'], $paramName => $name !== $defaultView ? $name : null, 'language' => $language], $params)),
                        'lastmod' => date(DATE_W3C, filectime($file)),
                    ];
                }
            }
        }
    }

    /**
     * Generates site maps from models.
     */
    private function generateModelUrls()
    {
        foreach ($this->models as $modelName) {
            /**
             * @var ActiveRecord $model
             */
            if (is_array($modelName)) {
                $model = new $modelName[is_numeric(key($modelName)) ? 0 : 'class'];

                if (isset($modelName['behaviors'])) {
                    if (isset($modelName['behaviors']['sitemap']) && empty($modelName['behaviors']['sitemap']['class'])) {
                        $modelName['behaviors']['sitemap']['class'] = SitemapBehavior::class;
                    }

                    $model->attachBehaviors($modelName['behaviors']);
                }
            } else {
                $model = new $modelName;
            }

            /**
             * @var SitemapBehavior $model
             */
            $this->urls = array_merge($this->urls, $model->generateSitemapUrls());
        }
    }
}