<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

final class SecurityHeadersTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testEveryPageLimitsItsReferrerAndItsContentType(): void
    {
        $this->open('/admin/account/login');

        self::assertResponseHeaderSame('referrer-policy', 'strict-origin-when-cross-origin');
        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
    }

    /**
     * The token in the URL would otherwise travel as the `Referer` of the page's own scripts and images.
     */
    public function testAPageCarryingATokenSendsNoReferrer(): void
    {
        $token = $this->getUserFromFixture('owner')->createPasswordResetToken();

        $this->open("/admin/account/reset?code=$token");

        self::assertResponseHeaderSame('referrer-policy', 'no-referrer');
    }
}
