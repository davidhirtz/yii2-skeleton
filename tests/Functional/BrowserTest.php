<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

class BrowserTest extends TestCase
{
    use FunctionalTestTrait;

    /**
     * The superglobals are process-wide while the application is rebuilt per test, so what a request writes into
     * them has to be put back when it ends — a header one request sends would otherwise be sent by every request
     * after it (#187), and the cookies and query string of the last one would still be there for the rest of the
     * test, where {@see \Hirtz\Skeleton\Modules\Admin\Module::getCookieColorScheme()} reads `$_COOKIE` (#202).
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
