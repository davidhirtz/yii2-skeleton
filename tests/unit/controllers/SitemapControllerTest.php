<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\controllers;

use Codeception\Test\Unit;
use Hirtz\Skeleton\controllers\SitemapController;
use SimpleXMLElement;
use Yii;

class SitemapControllerTest extends Unit
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

        self::assertEquals('https://www.example.com/sitemap.xml?key=urls&offset=0', $child->loc[0]);
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

    private function setSitemapUrls(): void
    {
        Yii::$app->sitemap->urls = [
            [
                'loc' => 'https://example.com/page-1',
                'lastmod' => '2024-01-01 10:00:00',
                'changefreq' => 'daily',
                'priority' => '1.0',
                'images' => [
                    [
                        'loc' => 'https://example.com/test-1.jpg',
                        'caption' => 'Test',
                    ],
                    [
                        'loc' => 'https://example.com/test-2.jpg',
                    ],
                ]
            ],
            [
                'loc' => 'https://example.com/page-2',
            ],
            [
                'loc' => 'https://example.com/page-3',
            ],
        ];
    }

    private function runIndexAction(?string $key = null): string
    {
        Yii::$app->controller = Yii::createObject(SitemapController::class, ['sitemap', Yii::$app]);
        return Yii::$app->controller->actionIndex($key);
    }

    private function setUseSitemapIndex(): void
    {
        Yii::$app->sitemap->useSitemapIndex = true;
        Yii::$app->sitemap->maxUrlCount = 2;
    }

    private function assertUrlset(SimpleXMLElement $xml): void
    {
        self::assertEquals('urlset', $xml->getName());

        self::assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getDocNamespaces()['']);
        self::assertEquals('http://www.google.com/schemas/sitemap-image/1.1', $xml->getDocNamespaces()['image']);

        $child = $xml->children()->children();
        self::assertEquals('https://example.com/page-1', $child->loc[0]);

        $image = $xml->url[0]->children('http://www.google.com/schemas/sitemap-image/1.1')->image;
        self::assertEquals('https://example.com/test-1.jpg', $image->loc[0]);
        self::assertEquals('Test', $image->caption[0]);
    }
}
