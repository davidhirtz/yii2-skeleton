<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Registry;

use Hirtz\Skeleton\Registry\Report;
use Hirtz\Skeleton\Test\TestCase;

class WebReportTest extends TestCase
{
    public function testTheUrlIsTheRequestHostUnderAWebApplication(): void
    {
        self::assertSame('https://www.test.localhost', Report::create()->toArray()['url']);
    }
}
