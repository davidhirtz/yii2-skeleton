<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UrlTest extends TestCase
{
    public function testSanitizeTrimsTheSurroundingSlashesAndWhitespace(): void
    {
        self::assertSame('de/page', Url::sanitize('/de/page/'));
        self::assertSame('de/page', Url::sanitize('  de/page  '));
        self::assertSame('www.example.com/de/page', Url::sanitize('/www.example.com/de/page'));
    }

    public function testSanitizeEncodesTheRemainingWhitespace(): void
    {
        self::assertSame('de/a%20page', Url::sanitize('de/a page'));
        self::assertSame('de/a%20page', Url::sanitize("de/a \n page"));
    }

    #[DataProvider('localPathDataProvider')]
    public function testALocalPathStaysOnThisHost(string $url, bool $local): void
    {
        self::assertSame($local, Url::isLocalPath($url));
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function localPathDataProvider(): array
    {
        return [
            ['/admin/dashboard?page=2', true],
            ['https://www.attacker.com', false],
            ['//www.attacker.com', false],
            ['/\\www.attacker.com', false],
            ["/\tadmin", false],
            ['admin', false],
        ];
    }

    public function testSanitizeAnswersAnEmptyStringForAnEmptyUrl(): void
    {
        self::assertSame('', Url::sanitize(null));
        self::assertSame('', Url::sanitize(false));
        self::assertSame('', Url::sanitize(''));
        self::assertSame('', Url::sanitize('/'));
    }
}
