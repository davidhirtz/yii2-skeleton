<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\CookieHelper;
use Hirtz\Skeleton\Test\TestCase;
use yii\web\Cookie;

class CookieHelperTest extends TestCase
{
    public function testEveryParentDomainButTheCookiesOwnIsExpired(): void
    {
        $headers = CookieHelper::getExpiredTwinHeaders($this->createCookie('.Example.com'), 'cms.www.example.com');

        self::assertSame([
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; HttpOnly',
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; HttpOnly; Domain=cms.www.example.com',
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; HttpOnly; Domain=www.example.com',
        ], $headers);
    }

    public function testAnIpAddressHasNoParentDomain(): void
    {
        $headers = CookieHelper::getExpiredTwinHeaders($this->createCookie('127.0.0.1'), '127.0.0.1');

        self::assertSame(['_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; HttpOnly'], $headers);
    }

    private function createCookie(string $domain): Cookie
    {
        $cookie = new Cookie();
        $cookie->name = '_session';
        $cookie->domain = $domain;
        $cookie->sameSite = '';

        return $cookie;
    }
}
