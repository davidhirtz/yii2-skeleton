<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

/**
 * The web server sends the header for the whole host; sent here too, a scanner reads the duplicate as neither.
 */
final class StrictTransportSecurityTest extends TestCase
{
    use FunctionalTestTrait;

    public function testTheHeaderIsLeftToTheWebServer(): void
    {
        $this->open('/sitemap.xml');

        self::assertResponseNotHasHeader('Strict-Transport-Security');
    }
}
