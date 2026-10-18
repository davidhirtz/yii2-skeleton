<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;

class ExpiredPageTest extends TestCase
{
    use FunctionalTestTrait;

    /**
     * @return array<string, array{string}>
     */
    public static function provideGuestForms(): array
    {
        return [
            'create' => ['/admin/account/create'],
            'login' => ['/admin/account/login'],
            'recover' => ['/admin/account/recover'],
            'resend' => ['/admin/account/resend'],
            'reset' => ['/admin/account/reset?code=unknown'],
        ];
    }

    #[DataProvider('provideGuestForms')]
    public function testAStaleTokenReloadsTheForm(string $url): void
    {
        $this->open('admin/account/login');
        self::$client->followRedirects(false);

        self::$crawler = self::$client->request(
            'POST',
            "https://www.test.localhost$url",
            ['_csrf' => 'stale', 'Form' => ['email' => 'stale@domain.com']],
            server: ['HTTP_HX_REQUEST' => 'true'],
        );

        self::assertResponseHeaderSame('hx-redirect', "https://www.test.localhost$url");
        self::assertSame('stale@domain.com', $this->getWebSession()->get('email'));
        self::assertSame([Yii::t('skeleton', 'ACCOUNT_ERROR_PAGE_EXPIRED')], $this->getWebSession()->getFlash('danger'));
    }
}
