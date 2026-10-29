<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Filters;

use Hirtz\Skeleton\Web\Application;
use Override;
use Yii;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

class PageCache extends \yii\filters\PageCache
{
    public const string TAG_DEPENDENCY_KEY = 'page-cache';

    /**
     * Triggered from `init()` before anything is derived from the configuration, so a listener may still change
     * the duration, the variations or whether the page is cached at all.
     */
    public const string EVENT_CONFIGURE = 'configure';

    public bool $disableForUsers = true;

    public bool $disableForPostRequests = true;

    /**
     * Honoured only with {@see $debug} or a session the request brought, so a visitor cannot force a full render.
     */
    public string|false $noCacheParam = 'nocache';

    public bool $debug = YII_DEBUG;

    /**
     * @var list<string>
     */
    public $only = ['index', 'view'];

    /**
     * The default GET param values {@see static::$variations} uses.
     *
     * @var array<string, mixed>
     */
    public array $params = [];

    public bool $useTagDependency = true;

    #[Override]
    public function init(): void
    {
        // A filter is a behavior, not a component: the event goes out through the class.
        Event::trigger($this, self::EVENT_CONFIGURE);

        $request = Application::current()->getRequest();

        if ($this->enabled) {
            $this->enabled = (!$this->disableForPostRequests || $request->getIsGet())
                && !$request->getIsDraft()
                && (!$this->disableForUsers || Application::current()->getUser()->getIsGuest())
                && !$this->isNoCacheRequest();
        }

        if ($this->useTagDependency) {
            $this->dependency = [
                'class' => TagDependency::class,
                'tags' => [self::TAG_DEPENDENCY_KEY],
                'reusable' => !$this->cacheCookies,
            ];
        }

        if (!is_callable($this->variations)) {
            $this->variations = $this->variations ? (array)$this->variations : [];
            $this->variations[] = $request->getIsAjaxRoute() ? '1' : '0';
            $this->variations[] = Yii::$app->language;

            foreach ($this->params as $param) {
                $value = $request->get($param, '');

                // An array (`?slug[]=1`) is a crafted request no page answers, so it is not worth a cache entry.
                if (!is_scalar($value)) {
                    $this->enabled = false;
                    $value = '';
                }

                $this->variations[] = (string)$value;
            }
        } elseif ($this->params) {
            throw new InvalidConfigException('PageCache::$params cannot be set if "variations" is callable.');
        }

        parent::init();
    }

    protected function isNoCacheRequest(): bool
    {
        return $this->noCacheParam
            && Application::current()->getRequest()->get($this->noCacheParam)
            && ($this->debug || Application::current()->getSession()->isStarted());
    }

    /**
     * A page built on a host the request claimed links that host, so it is cached for that host alone.
     *
     * @return array<array-key, mixed>
     */
    #[Override]
    protected function calculateCacheKey(): array
    {
        $key = parent::calculateCacheKey();
        $manager = Yii::$app->getUrlManager();

        if ($manager->isHostInfoFromRequest()) {
            $key[] = $manager->getHostInfo();
        }

        return $key;
    }
}
