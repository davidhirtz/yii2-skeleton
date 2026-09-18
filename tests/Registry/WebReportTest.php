<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Registry;

use Hirtz\Skeleton\Registry\Report;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class WebReportTest extends TestCase
{
    /**
     * The tenant bundle re-points the report in the container; this tests the skeleton's own.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$container->clear(Report::class);
    }

    public function testTheUrlIsTheRequestHostUnderAWebApplication(): void
    {
        self::assertSame('https://www.test.localhost', Report::create()->toArray()['url']);
    }
}
