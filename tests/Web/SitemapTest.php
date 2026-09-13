<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Behaviors\SitemapBehavior;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Sitemap;
use Override;
use Yii;

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

        $locations = array_column($sitemap->generateFileUrls(), 'loc');

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

        $locations = array_column($sitemap->generateFileUrls(), 'loc');

        self::assertContains(['site/view'], $locations);
        self::assertContains(['site/view', 'view' => 'index'], $locations);
    }

    public function testGenerateFileUrlsWithoutAParamNameEmitsOneRoute(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [[...$this->getViewConfig(), 'paramName' => false]],
        ]);

        $locations = array_column($sitemap->generateFileUrls(), 'loc');

        self::assertSame([['site/view'], ['site/view'], ['site/view']], $locations);
    }

    public function testGenerateFileUrlsRepeatsEveryConfiguredLanguage(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [[...$this->getViewConfig(), 'languages' => ['en-US', 'de']]],
        ]);

        $urls = $sitemap->generateFileUrls();

        self::assertCount(6, $urls);
        self::assertContains(['site/view', 'language' => 'de'], array_column($urls, 'loc'));
    }

    public function testGenerateFileUrlsWithoutARouteIsSkipped(): void
    {
        $sitemap = $this->createSitemap([
            'views' => [['alias' => $this->viewPath]],
        ]);

        self::assertSame([], $sitemap->generateFileUrls());
    }

    public function testGenerateUrlsMergesUrlsViewsAndModels(): void
    {
        $sitemap = $this->createSitemap([
            'urls' => [['loc' => '/manual']],
            'views' => [$this->getViewConfig()],
            'models' => [TestSitemapUser::class],
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
            'models' => ['users' => TestSitemapUser::class],
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
            'models' => ['users' => TestSitemapUser::class],
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
        self::assertNull($sitemaps[1]['lastmod']);
        self::assertArrayNotHasKey('lastmod', $sitemaps[2]);
    }

    /**
     * A model that does not declare the behavior itself carries it in the configuration, where `class` defaults to
     * `SitemapBehavior`.
     */
    public function testAModelCanCarryItsSitemapBehaviorInTheConfiguration(): void
    {
        $sitemap = $this->createSitemap([
            'models' => [
                [
                    'class' => User::class,
                    'behaviors' => [
                        'sitemap' => [
                            'callback' => fn (User $user): array => ['loc' => ['/user/view', 'id' => $user->id]],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertCount(3, $sitemap->generateUrls());
    }

    public function testTheVariationsCallbackIsResolvedOnInit(): void
    {
        $sitemap = $this->createSitemap([
            'variations' => fn (): array => [Yii::$app->language],
        ]);

        self::assertSame(['en-US'], $sitemap->variations);
    }

    private function getViewConfig(): array
    {
        return [
            'alias' => $this->viewPath,
            'route' => 'site/view',
        ];
    }

    /**
     * `SitemapBehavior` reads `useSitemapIndex` and `maxUrlCount` off the application component, so the component is
     * what a test has to replace.
     */
    private function createSitemap(array $config = []): Sitemap
    {
        Yii::$app->set('sitemap', [...$config, 'class' => Sitemap::class]);

        /** @var Sitemap $sitemap */
        $sitemap = Yii::$app->get('sitemap');

        return $sitemap;
    }
}

class TestSitemapUser extends User
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'sitemap' => [
                'class' => SitemapBehavior::class,
                'callback' => fn (User $user): array => ['loc' => ['/user/view', 'id' => $user->id]],
            ],
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return User::tableName();
    }
}
