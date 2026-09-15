<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Sitemap;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Sitemap\ModelSitemap;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class SitemapTest extends TestCase
{
    use UserFixtureTrait;

    private string $viewPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->viewPath = Yii::getAlias('@runtime/test-views') . '/';
        FileHelper::createDirectory($this->viewPath);

        foreach (['index.php', 'about.php', 'contact.php', '_partial.php', 'error.php'] as $file) {
            file_put_contents($this->viewPath . $file, '');
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->viewPath);
        parent::tearDown();
    }

    public function testGenerateFileUrlsSkipsPartialsAndTheErrorView(): void
    {
        $sitemap = $this->createSitemap(['views' => [$this->getViewConfig()]]);

        $locations = array_column($sitemap->generateUrls(), 'loc');

        self::assertEqualsCanonicalizing([
            ['site/view'],
            ['site/view', 'view' => 'about'],
            ['site/view', 'view' => 'contact'],
        ], $locations);
    }

    /**
     * The default view carries no view parameter, so the route stays the bare one.
     */
    public function testGenerateFileUrlsHonoursTheDefaultView(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [[...$this->getViewConfig(), 'defaultView' => 'about']],
        ]);

        $locations = array_column($sitemap->generateUrls(), 'loc');

        self::assertContains(['site/view'], $locations);
        self::assertContains(['site/view', 'view' => 'index'], $locations);
    }

    public function testGenerateFileUrlsWithoutAParamNameEmitsOneRoute(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [[...$this->getViewConfig(), 'paramName' => false]],
        ]);

        $locations = array_column($sitemap->generateUrls(), 'loc');

        self::assertSame([['site/view'], ['site/view'], ['site/view']], $locations);
    }

    public function testGenerateFileUrlsRepeatsEveryConfiguredLanguage(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [[...$this->getViewConfig(), 'languages' => ['en-US', 'de']]],
        ]);

        $urls = $sitemap->generateUrls();

        self::assertCount(6, $urls);
        self::assertContains(['site/view', 'language' => 'de'], array_column($urls, 'loc'));
    }

    public function testGenerateFileUrlsWithoutARouteIsSkipped(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [['alias' => $this->viewPath]],
        ]);

        self::assertSame([], $sitemap->generateUrls());
    }

    public function testGenerateUrlsMergesUrlsViewsAndModels(): void
    {
        $sitemap = $this->createSitemap([
            'urls' => [['loc' => '/manual']],
            'views' => [$this->getViewConfig()],
            'sitemaps' => ['users' => $this->getUserSitemapConfig()],
        ]);

        $locations = array_column($sitemap->generateUrls(), 'loc');

        self::assertContains('/manual', $locations);
        self::assertContains(['site/view', 'view' => 'about'], $locations);
        self::assertContains(['/user/view', 'id' => $this->getUserFromFixture('owner')->id], $locations);
    }

    /**
     * With an index, one request renders one sitemap: the key names the set and the offset the page within it.
     */
    public function testGenerateUrlsWithAnIndexRendersOneSetAtATime(): void
    {
        $sitemap = $this->createSitemap([
            'useSitemapIndex' => true,
            'maxUrlCount' => 2,
            'urls' => [['loc' => '/a'], ['loc' => '/b'], ['loc' => '/c']],
            'sitemaps' => ['users' => $this->getUserSitemapConfig()],
        ]);

        self::assertSame(['/a', '/b'], array_column($sitemap->generateUrls('urls'), 'loc'));
        self::assertSame(['/c'], array_column($sitemap->generateUrls('urls', 1), 'loc'));

        self::assertCount(2, $sitemap->generateUrls('users'));
        self::assertCount(1, $sitemap->generateUrls('users', 1));
        self::assertSame([], $sitemap->generateUrls('nope'));
    }

    public function testGenerateIndexUrlsPagesTheUrlsAndTheModels(): void
    {
        $sitemap = $this->createSitemap([
            'useSitemapIndex' => true,
            'maxUrlCount' => 2,
            'urls' => [
                ['loc' => '/a', 'lastmod' => '2024-01-01 10:00:00'],
                ['loc' => '/b', 'lastmod' => '2026-01-01 10:00:00'],
                ['loc' => '/c'],
            ],
            'sitemaps' => ['users' => $this->getUserSitemapConfig()],
        ]);

        $sitemaps = $sitemap->generateIndexUrls();

        self::assertSame([
            ['sitemap/index', 'key' => 'urls', 'offset' => 0],
            ['sitemap/index', 'key' => 'urls', 'offset' => 1],
            ['sitemap/index', 'key' => 'users', 'offset' => 0],
            ['sitemap/index', 'key' => 'users', 'offset' => 1],
        ], array_column($sitemaps, 'loc'));

        // the newest `lastmod` of the set wins, and a set without one reports none
        self::assertSame('2026-01-01 10:00:00', $sitemaps[0]['lastmod']);
        self::assertArrayNotHasKey('lastmod', $sitemaps[1]);
        self::assertArrayNotHasKey('lastmod', $sitemaps[2]);
    }

    /**
     * The pages are counted from the records, so a record the URL generation skips shortens its page rather than
     * pulling a URL forward from the next one.
     */
    public function testARecordWithoutAUrlShortensItsPage(): void
    {
        $sitemap = $this->createSitemap([
            'useSitemapIndex' => true,
            'sitemaps' => [
                'users' => [
                    ...$this->getUserSitemapConfig(),
                    'url' => fn (): bool => false,
                ],
            ],
        ]);

        self::assertSame(1, $sitemap->getSitemap('users')?->getPageCount());
        self::assertSame([], $sitemap->generateUrls('users'));
        self::assertCount(1, $sitemap->generateIndexUrls());
    }

    public function testTheUrlsKeyIsReserved(): void
    {
        $sitemap = $this->createSitemap(['sitemaps' => ['urls' => $this->getUserSitemapConfig()]]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The sitemap key "urls" is reserved.');

        $sitemap->getSitemaps();
    }

    public function testAModelClassIsRequired(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('::$modelClass must extend');

        $this->createSitemap(['sitemaps' => ['broken' => ModelSitemap::class]])->getSitemaps();
    }

    public function testAUrlWithoutALocationThrows(): void
    {
        $sitemap = $this->createSitemap([
            'sitemaps' => [
                'users' => [
                    ...$this->getUserSitemapConfig(),
                    'url' => fn (User $user): array => ['url' => $user->id],
                ],
            ],
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('must return an array with a "loc" key.');

        $sitemap->generateUrls();
    }

    public function testTheDefaultChangeFrequencyAndPriorityAreApplied(): void
    {
        $sitemap = $this->createSitemap([
            'sitemaps' => [
                'users' => [
                    ...$this->getUserSitemapConfig(),
                    'changeFrequency' => 'weekly',
                    'priority' => 0.8,
                ],
            ],
        ]);

        $url = $sitemap->generateUrls()[0];

        self::assertSame('weekly', $url['changefreq']);
        self::assertSame(0.8, $url['priority']);
    }

    /**
     * A single factor is what the tenant bundle configures, and the page cache needs a list.
     */
    public function testTheVariationsCallbackIsResolvedToAList(): void
    {
        self::assertSame(['en-US'], $this->createSitemap([
            'variations' => fn (): array => [Yii::$app->language],
        ])->getVariations());

        self::assertSame(['1'], $this->createSitemap(['variations' => fn (): int => 1])->getVariations());
        self::assertSame([], $this->createSitemap(['variations' => fn (): ?int => null])->getVariations());
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewConfig(): array
    {
        return [
            'alias' => $this->viewPath,
            'route' => 'site/view',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserSitemapConfig(): array
    {
        return [
            'class' => ModelSitemap::class,
            'modelClass' => User::class,
            'url' => fn (User $user): array => ['loc' => ['/user/view', 'id' => $user->id]],
        ];
    }

    /**
     * A sitemap reads `maxUrlCount` off the application component, so the component is what a test has to replace.
     *
     * @param array<string, mixed> $config
     */
    private function createSitemap(array $config = []): Sitemap
    {
        Yii::$app->set('sitemap', [...$config, 'class' => Sitemap::class]);

        return Sitemap::getComponent();
    }
}
