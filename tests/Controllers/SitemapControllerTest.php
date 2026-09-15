<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Controllers;

use Hirtz\Skeleton\Controllers\SitemapController;
use Hirtz\Skeleton\Test\TestCase;
use SimpleXMLElement;
use Yii;
use yii\web\NotFoundHttpException;

class SitemapControllerTest extends TestCase
{
    public function testIndexWithNoUrls(): void
    {
        $result = $this->runIndexAction();

        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>', $result);

        $xml = new SimpleXMLElement($result);

        self::assertCount(0, $xml->children());
    }

    public function testIndexWithUrls(): void
    {
        $this->setSitemapUrls();

        $result = $this->runIndexAction();
        $xml = new SimpleXMLElement($result);

        self::assertUrlset($xml);
        self::assertCount(3, $xml->children());
    }

    public function testIndexWithSitemapIndex(): void
    {
        $this->setSitemapUrls();
        $this->setUseSitemapIndex();

        $result = $this->runIndexAction();

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $result);

        $xml = new SimpleXMLElement($result);

        self::assertEquals('sitemapindex', $xml->getName());
        self::assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()['']);
        self::assertCount(2, $xml->children());

        $child = $xml->children()->children();

        self::assertEquals('https://www.test.localhost/sitemap.xml?key=urls&offset=0', $child->loc[0]);
        self::assertEquals('2024-01-01 10:00:00', $child->lastmod[0]);
    }

    public function testIndexWithSitemapIndexAndKey(): void
    {
        $this->setSitemapUrls();
        $this->setUseSitemapIndex();

        $result = $this->runIndexAction('urls');

        $xml = new SimpleXMLElement($result);

        self::assertUrlset($xml);
        self::assertCount(2, $xml->children());
    }

    public function testIndexWithSitemapIndexAndKeyCached(): void
    {
        $this->setSitemapUrls();
        Yii::$app->sitemap->cache = 'cache';

        $this->runIndexAction('urls');
        $result = $this->runIndexAction('urls');

        $xml = new SimpleXMLElement($result);

        self::assertUrlset($xml);
        self::assertCount(3, $xml->children());
    }

    /**
     * An unknown set — and a page past the end of a known one — is a 404 rather than an empty but valid sitemap.
     */
    public function testIndexWithSitemapIndexAndUnknownKey(): void
    {
        $this->setSitemapUrls();
        $this->setUseSitemapIndex();

        $this->expectException(NotFoundHttpException::class);

        $this->runIndexAction('nope');
    }

    public function testIndexWithSitemapIndexAndOffsetPastTheEnd(): void
    {
        $this->setSitemapUrls();
        $this->setUseSitemapIndex();

        $this->expectException(NotFoundHttpException::class);

        $this->runIndexAction('urls', 2);
    }

    /**
     * XMLWriter drops an attribute written after the first child, so the image namespace has to be declared before
     * the URLs are written — the first one carrying images is rarely the first one.
     */
    public function testIndexDeclaresTheImageNamespaceForALaterUrl(): void
    {
        $this->setSitemapUrls();
        Yii::$app->sitemap->urls = array_reverse(Yii::$app->sitemap->urls);

        $xml = new SimpleXMLElement($this->runIndexAction());

        $namespaces = $xml->getDocNamespaces();
        self::assertIsArray($namespaces);
        self::assertArrayHasKey('image', $namespaces);
    }

    private function setSitemapUrls(): void
    {
        Yii::$app->sitemap->urls = [
            [
                'loc' => 'https://test.localhost/page-1',
                'lastmod' => '2024-01-01 10:00:00',
                'changefreq' => 'daily',
                'priority' => '1.0',
                'images' => [
                    [
                        'loc' => 'https://test.localhost/test-1.jpg',
                        'caption' => 'Test',
                    ],
                    [
                        'loc' => 'https://test.localhost/test-2.jpg',
                    ],
                ]
            ],
            [
                'loc' => 'https://test.localhost/page-2',
            ],
            [
                'loc' => 'https://test.localhost/page-3',
            ],
        ];
    }

    private function runIndexAction(?string $key = null, int $offset = 0): string
    {
        Yii::$app->controller = Yii::createObject(SitemapController::class, ['sitemap', Yii::$app]);
        return Yii::$app->controller->actionIndex($key, $offset);
    }

    private function setUseSitemapIndex(): void
    {
        Yii::$app->sitemap->useSitemapIndex = true;
        Yii::$app->sitemap->maxUrlCount = 2;
    }

    private function assertUrlset(SimpleXMLElement $xml): void
    {
        self::assertEquals('urlset', $xml->getName());

        $namespaces = $xml->getDocNamespaces();
        self::assertIsArray($namespaces);

        self::assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', $namespaces['']);
        self::assertEquals('http://www.google.com/schemas/sitemap-image/1.1', $namespaces['image']);

        $child = $xml->children()->children();
        self::assertEquals('https://test.localhost/page-1', $child->loc[0]);

        $image = $xml->url[0]->children('http://www.google.com/schemas/sitemap-image/1.1')->image;
        self::assertEquals('https://test.localhost/test-1.jpg', $image->loc[0]);
        self::assertEquals('Test', $image->caption[0]);
    }
}
