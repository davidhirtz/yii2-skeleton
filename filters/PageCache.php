<?php

namespace davidhirtz\yii2\skeleton\filters;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

class PageCache extends \yii\filters\PageCache
{
    public const TAG_DEPENDENCY_KEY = 'page-cache';

    /**
     * @var bool|null leave null to use default value
     */
    public $enabled = null;

    /**
     * @var string|false GET param name to disable caching, set to `false` to disable cache skipping
     */
    public string|false $noCacheParam = 'nocache';

    /**
     * @var string[]
     */
    public $only = ['index', 'view'];

    /**
     * @var array the default GET param values used by {@see static::$variations}
     */
    public array $params = [];

    /**
     * @return void
     */
    public function init(): void
    {
        $request = Yii::$app->getRequest();

        $this->dependency ??= [
            'class' => TagDependency::class,
            'tags' => [self::TAG_DEPENDENCY_KEY],
            'reusable' => !$this->cacheCookies,
        ];

        $this->enabled ??= Yii::$app->getRequest()->getIsGet() &&
            Yii::$app->getUser()->getIsGuest() &&
            (!$this->noCacheParam || !$request->get($this->noCacheParam));

        if (!is_callable($this->variations)) {
            $this->variations ??= [];
            $this->variations[] = $request->getIsAjaxRoute();
            $this->variations[] = Yii::$app->language;

            foreach ($this->params as $param) {
                $this->variations[] = $request->get($param, '');
            }
        } elseif ($this->params) {
            throw new InvalidConfigException('PageCache::$params cannot be set if "variations" is callable.');
        }

        parent::init();
    }
}