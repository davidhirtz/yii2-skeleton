<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yii;
use yii\web\Cookie;

class CookieDomainTest extends TestCase
{
    #[DataProvider('cookieDomainDataProvider')]
    public function testTheCookieDomainAppliesOnlyToItsOwnHosts(string $cookieDomain, bool $applies): void
    {
        $this->config['params']['cookieDomain'] = $cookieDomain;
        Yii::$container->clear(Cookie::class);
        $this->reloadApplication();

        $definition = Yii::$container->getDefinitions()[Cookie::class] ?? [];

        self::assertSame($applies ? $cookieDomain : null, $definition['domain'] ?? null);
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function cookieDomainDataProvider(): array
    {
        // The test host is `www.test.localhost`.
        return [
            ['.test.localhost', true],
            ['test.localhost', true],
            ['www.test.localhost', true],
            ['st.localhost', false],
            ['example.com', false],
        ];
    }
}
