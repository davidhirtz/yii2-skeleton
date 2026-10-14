<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Filters;

use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\UrlManager;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

class PageCacheTest extends TestCase
{
    use UserFixtureTrait;

    public function testAGuestGetRequestIsCached(): void
    {
        self::assertTrue($this->createPageCache()->enabled);
    }

    public function testALoggedInUserIsNotCached(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));

        self::assertFalse($this->createPageCache()->enabled);
        self::assertTrue($this->createPageCache(['disableForUsers' => false])->enabled);
    }

    public function testAPostRequestIsNotCached(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        self::assertFalse($this->createPageCache()->enabled);
        self::assertTrue($this->createPageCache(['disableForPostRequests' => false])->enabled);
    }

    public function testTheNoCacheParamSkipsTheCache(): void
    {
        $_GET['nocache'] = '1';

        self::assertFalse($this->createPageCache()->enabled);
        self::assertTrue($this->createPageCache(['noCacheParam' => false])->enabled);
        self::assertTrue($this->createPageCache(['noCacheParam' => 'other'])->enabled);
    }

    /**
     * A draft host renders unpublished content, which must never reach the shared cache.
     */
    public function testADraftRequestIsNotCached(): void
    {
        $this->getWebRequest()->setIsDraft(true);

        self::assertFalse($this->createPageCache()->enabled);
        self::assertFalse($this->createPageCache(['disableForUsers' => false])->enabled);
        self::assertFalse($this->createPageCache(['disableForPostRequests' => false])->enabled);
        self::assertFalse($this->createPageCache(['noCacheParam' => false])->enabled);
    }

    public function testAFilterThatIsSwitchedOffStaysOff(): void
    {
        self::assertFalse($this->createPageCache(['enabled' => false])->enabled);
    }

    public function testTheTagDependencyIsConfigured(): void
    {
        $filter = $this->createPageCache();

        self::assertIsArray($filter->dependency);
        self::assertSame(TagDependency::class, $filter->dependency['class']);
        self::assertSame([PageCache::TAG_DEPENDENCY_KEY], $filter->dependency['tags']);
        self::assertTrue($filter->dependency['reusable']);

        // a cached cookie is per visitor, so the dependency cannot be shared between requests
        $cookieDependency = $this->createPageCache(['cacheCookies' => true])->dependency;
        self::assertIsArray($cookieDependency);
        self::assertFalse($cookieDependency['reusable']);
        self::assertEmpty($this->createPageCache(['useTagDependency' => false])->dependency);
    }

    public function testTheVariationsCoverTheLanguageAndTheAjaxRoute(): void
    {
        Yii::$app->language = 'de';

        $filter = $this->createPageCache();

        self::assertSame(['0', 'de'], $filter->variations);
    }

    public function testTheConfiguredParamsAreAddedToTheVariations(): void
    {
        $_GET['page'] = '2';

        $filter = $this->createPageCache(['params' => ['page', 'category']]);

        self::assertSame(['0', 'en-US', '2', ''], $filter->variations);
    }

    public function testAnArrayParamIsNotCached(): void
    {
        $_GET['slug'] = ['1'];

        $filter = $this->createPageCache(['params' => ['slug']]);

        self::assertFalse($filter->enabled);
        self::assertSame(['0', 'en-US', ''], $filter->variations);
    }

    public function testTheConfiguredVariationsAreKept(): void
    {
        $filter = $this->createPageCache(['variations' => ['own']]);

        self::assertSame(['own', '0', 'en-US'], $filter->variations);
    }

    public function testCallableVariationsAndParamsCannotBeCombined(): void
    {
        $this->expectException(InvalidConfigException::class);

        $this->createPageCache([
            'variations' => fn (): array => [],
            'params' => ['page'],
        ]);
    }

    public function testAHostTakenFromTheRequestVariesTheKeyAndTheSitemap(): void
    {
        // The tenant bundle points the container at its own subclass, which pins the host of the tenant it resolves.
        Yii::$container->clear(UrlManager::class);
        Yii::$app->set('urlManager', ['class' => UrlManager::class]);

        $keys = [];
        $variations = [];

        foreach (['https://www.example.com', 'https://evil.localhost'] as $hostInfo) {
            Yii::$app->set('request', ['class' => Request::class, 'hostInfo' => $hostInfo, 'url' => '/']);
            Yii::$app->getUrlManager()->parseRequest($this->getWebRequest());

            $keys[] = new TestPageCache()->getCacheKey();
            $variations[] = new Sitemap()->getVariations();
        }

        self::assertContains('https://evil.localhost', $keys[1]);
        self::assertNotSame($keys[0], $keys[1]);
        self::assertSame([['https://www.example.com'], ['https://evil.localhost']], $variations);
    }

    public function testAConfiguredHostDoesNotVaryTheKey(): void
    {
        Yii::$container->clear(UrlManager::class);
        Yii::$app->set('urlManager', ['class' => UrlManager::class, 'hostInfo' => 'https://www.example.com']);
        Yii::$app->getUrlManager()->parseRequest($this->getWebRequest());

        self::assertNotContains('https://www.example.com', new TestPageCache()->getCacheKey());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPageCache(array $config = []): PageCache
    {
        return new PageCache($config);
    }
}

class TestPageCache extends PageCache
{
    /**
     * @return array<array-key, mixed>
     */
    public function getCacheKey(): array
    {
        return $this->calculateCacheKey();
    }
}
