<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Filters;

use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
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

        self::assertSame(TagDependency::class, $filter->dependency['class']);
        self::assertSame([PageCache::TAG_DEPENDENCY_KEY], $filter->dependency['tags']);
        self::assertTrue($filter->dependency['reusable']);

        // a cached cookie is per visitor, so the dependency cannot be shared between requests
        self::assertFalse($this->createPageCache(['cacheCookies' => true])->dependency['reusable']);
        self::assertEmpty($this->createPageCache(['useTagDependency' => false])->dependency);
    }

    public function testTheVariationsCoverTheLanguageAndTheAjaxRoute(): void
    {
        Yii::$app->language = 'de';

        $filter = $this->createPageCache();

        self::assertSame([false, 'de'], $filter->variations);
    }

    public function testTheConfiguredParamsAreAddedToTheVariations(): void
    {
        $_GET['page'] = '2';

        $filter = $this->createPageCache(['params' => ['page', 'category']]);

        self::assertSame([false, 'en-US', '2', ''], $filter->variations);
    }

    public function testTheConfiguredVariationsAreKept(): void
    {
        $filter = $this->createPageCache(['variations' => ['own']]);

        self::assertSame(['own', false, 'en-US'], $filter->variations);
    }

    public function testCallableVariationsAndParamsCannotBeCombined(): void
    {
        $this->expectException(InvalidConfigException::class);

        $this->createPageCache([
            'variations' => fn (): array => [],
            'params' => ['page'],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPageCache(array $config = []): PageCache
    {
        return new PageCache($config);
    }
}
