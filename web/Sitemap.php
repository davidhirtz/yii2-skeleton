<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\behaviors\SitemapBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\Component;
use yii\caching\Cache;

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
     * @return string
     */
    private function generateFileUrls()
    {
        $manager = Yii::$app->getUrlManager();
        $languages = $manager->i18nUrl ? array_keys($manager->languages) : [null];

        foreach ($this->views as $view) {
            $exclude = ArrayHelper::getValue($view, 'exclude', []);
            $params = ArrayHelper::getValue($view, 'params', []);
            $paramName = ArrayHelper::getValue($view, 'paramName', 'view');

            foreach (glob(Yii::getAlias($view['alias']) . '/*.php') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);

                if (!in_array($name, $exclude)) {
                    foreach ($languages as $language) {
                        $this->urls[] = [
                            'loc' => array_filter(array_merge([
                                $view['route'],
                                $paramName => $name,
                                'language' => $language
                            ], $params)),
                            'lastmod' => date(DATE_W3C, filectime($file)),
                        ];
                    }
                }
            }
        }
    }

    /**
     * Generates site maps from models.
     * @return string
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