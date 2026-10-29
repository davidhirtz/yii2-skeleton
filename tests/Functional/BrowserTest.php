<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

class BrowserTest extends TestCase
{
    use FunctionalTestTrait;

    /**
     * The superglobals are process-wide, so a request's headers, cookies and query string must not reach the next
     * (#187, #202).
     */
    public function testARequestDoesNotOutliveItself(): void
    {
        $_COOKIE['probe'] = 'kept';
        $server = $_SERVER;

        $this->open('admin/account/login');

        self::$client->request(
            'GET',
            'https://www.test.localhost/admin/account/login?probe=1',
            server: ['HTTP_HX_REQUEST' => 'true'],
        );

        self::assertNotNull(self::$client->getCookieJar()->get('_csrf'));

        self::assertSame($server, $_SERVER);
        self::assertSame(['probe' => 'kept'], $_COOKIE);
        self::assertSame([], $_GET);
    }
}
