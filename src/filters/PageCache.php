<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\filters;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

class PageCache extends \yii\filters\PageCache
{
    public const string TAG_DEPENDENCY_KEY = 'page-cache';

    /**
     * @var bool whether to cache the response for logged-in users
     */
    public bool $disableForUsers = true;

    /**
     * @var bool whether to cache the response for POST requests
     */
    public bool $disableForPostRequests = true;

    /**
     * @var string|false GET param name to disable caching, set to `false` to disable cache skipping
     */
    public string|false $noCacheParam = 'nocache';

    public $only = ['index', 'view'];

    /**
     * @var array the default GET param values used by {@see static::$variations}
     */
    public array $params = [];

    /**
     * @var bool whether to use the tag dependency as a dependency
     */
    public bool $useTagDependency = true;

    public function init(): void
    {
        $request = Yii::$app->getRequest();

        if ($this->enabled) {
            $this->enabled = (!$this->disableForPostRequests || $request->getIsGet())
                && !$request->getIsDraft()
                && (!$this->disableForUsers || Yii::$app->getUser()->getIsGuest())
                && (!$this->noCacheParam || !$request->get($this->noCacheParam));
        }

        if ($this->useTagDependency) {
            $this->dependency = [
                'class' => TagDependency::class,
                'tags' => [self::TAG_DEPENDENCY_KEY],
                'reusable' => !$this->cacheCookies,
            ];
        }

        if (!is_callable($this->variations)) {
            $this->variations = $this->variations ?: [];
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
