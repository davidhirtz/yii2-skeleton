<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Test\TestCase;

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

    public function testSanitizeAnswersAnEmptyStringForAnEmptyUrl(): void
    {
        self::assertSame('', Url::sanitize(null));
        self::assertSame('', Url::sanitize(false));
        self::assertSame('', Url::sanitize(''));
        self::assertSame('', Url::sanitize('/'));
    }
}
