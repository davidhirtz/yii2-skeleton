<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use DateTimeZone;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class StructuredDataTest extends TestCase
{
    public function testIdReplacesAFragment(): void
    {
        self::assertSame('https://example.com/a#event', StructuredData::id('https://example.com/a', 'event'));
        self::assertSame('https://example.com/a#event', StructuredData::id('https://example.com/a#top', 'event'));
    }

    public function testADateTimeCarriesTheOffsetOfTheApplicationZone(): void
    {
        $date = new DateTime('2026-10-09 16:00:00', new DateTimeZone('UTC'));
        $previous = Yii::$app->getTimeZone();

        try {
            Yii::$app->setTimeZone('Europe/Berlin');
            self::assertSame('2026-10-09T18:00:00+02:00', StructuredData::date($date));
        } finally {
            Yii::$app->setTimeZone($previous);
        }
    }

    public function testADateIsADay(): void
    {
        self::assertSame('2026-10-09', StructuredData::date(new Date('2026-10-09')));
        self::assertNull(StructuredData::date(null));
    }

    public function testImage(): void
    {
        self::assertSame(
            ['@type' => 'ImageObject', 'url' => 'https://www.test.localhost/logo.png', 'width' => 600],
            StructuredData::image('/logo.png', 600),
        );

        self::assertNull(StructuredData::image(null));
    }

    public function testFilterDropsNullAndEmptyStringsAtEveryLevel(): void
    {
        self::assertSame([
            'name' => 'Å',
            'count' => 0,
            'isAccessibleForFree' => false,
            'itemListElement' => [],
            'offers' => ['url' => 'https://example.com'],
            'sameAs' => ['a', 'b'],
        ], StructuredData::filter([
            'name' => 'Å',
            'description' => null,
            'alternateName' => '',
            'count' => 0,
            'isAccessibleForFree' => false,
            'itemListElement' => [],
            'offers' => ['url' => 'https://example.com', 'price' => null],
            'sameAs' => ['a', null, 'b'],
        ]));
    }

    public function testEncodeEscapesTheScriptAndKeepsText(): void
    {
        $name = "</script><script>alert(1)</script> Å ą Ж 東京 🎉 Mu\u{308}ller";
        $json = StructuredData::encode([['@type' => 'Thing', 'name' => $name]]);

        self::assertStringNotContainsString('</script>', $json);
        self::assertSame(
            ['@context' => 'https://schema.org', '@type' => 'Thing', 'name' => $name],
            json_decode($json, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testSeveralNodesAreAGraph(): void
    {
        $json = StructuredData::encode([['@type' => 'WebSite'], ['@type' => 'WebPage']]);

        self::assertSame(
            '{"@context":"https:\/\/schema.org","@graph":[{"@type":"WebSite"},{"@type":"WebPage"}]}',
            $json,
        );
    }
}
