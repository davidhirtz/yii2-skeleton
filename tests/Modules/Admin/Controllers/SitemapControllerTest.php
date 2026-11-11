<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Sitemap\Sitemap;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Yii;
use yii\web\NotFoundHttpException;

class SitemapControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testASitemapWithoutConfigurationIsNotFound(): void
    {
        $this->login();
        self::assertFalse(Sitemap::getComponent()->isEnabled());

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/sitemap/index');
    }

    public function testAnyLoggedInUserSeesTheUrlsAsTheSitemapWritesThem(): void
    {
        $this->login();
        $this->setSitemap(['urls' => [
            ['loc' => '/å-東京-😀', 'lastmod' => '2026-11-01'],
            ['loc' => '/images', 'images' => [['loc' => '/a.jpg'], ['loc' => '/b.jpg']]],
        ]]);

        $html = Yii::$app->runAction('admin/sitemap/index');

        self::assertIsString($html);
        self::assertStringContainsString('href="' . Url::to('/å-東京-😀', true) . '"', $html);
        self::assertStringContainsString('<x-timeago', $html);
        self::assertStringContainsString(Yii::t('skeleton', 'SITEMAP_IMAGES_LABEL'), $html);
        self::assertStringContainsString(Yii::t('skeleton', 'SITEMAP_URL_COUNT_TEXT', ['count' => 2]), $html);
        self::assertStringContainsString(Html::encode(Yii::t('skeleton', 'SITEMAP_INDEX_HINT')), $html);
    }

    public function testEachPageOfTheIndexIsATabAndAPageBeyondIsNotFound(): void
    {
        $this->login();
        $this->setSitemap([
            'useSitemapIndex' => true,
            'maxUrlCount' => 1,
            'urls' => [['loc' => '/first'], ['loc' => '/second']],
        ]);

        $html = Yii::$app->runAction('admin/sitemap/index', ['key' => Sitemap::URLS_KEY, 'offset' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString(Url::to('/second', true), $html);
        self::assertStringNotContainsString(Url::to('/first', true) . '"', $html);
        self::assertStringContainsString(Yii::t('skeleton', 'SITEMAP_URLS_LABEL') . ' 2', $html);

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/sitemap/index', ['key' => Sitemap::URLS_KEY, 'offset' => 2]);
    }

    public function testTheDashboardLinksTheSitemapOnlyWhenItListsSomething(): void
    {
        $this->login();
        $url = 'href="' . Url::to(['/admin/sitemap/index']) . '"';

        self::assertStringNotContainsString($url, Dashboard::make()->render());

        $this->setSitemap(['urls' => ['/page']]);
        self::assertStringContainsString($url, Dashboard::make()->render());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function setSitemap(array $config): void
    {
        Yii::$app->set('sitemap', [...$config, 'class' => Sitemap::class]);
    }

    /**
     * The fixture's admin holds no role: being logged in is enough.
     */
    private function login(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
    }
}
