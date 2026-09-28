<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Modules\Admin\Widgets\HostAlert;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\UrlManager;
use Yii;

class HostAlertTest extends TestCase
{
    public function testTheAlertIsInvisibleOnANonProductionHost(): void
    {
        $this->parseRequest();

        $alert = HostAlert::make();

        self::assertSame('', $alert->render());
        self::assertFalse($alert->getUnpinned());
    }

    public function testAProductionHostTakingItsHostFromTheRequestIsWarnedAbout(): void
    {
        $this->getWebRequest()->setHostInfo('https://www.example.com');
        $this->parseRequest();

        $alert = HostAlert::make();
        $html = $alert->render();

        self::assertTrue($alert->getUnpinned());
        self::assertStringContainsString('data-alert="warning"', $html);
        self::assertStringContainsString('allowedHosts', $html);
    }

    public function testAllowedHostsSilenceTheAlert(): void
    {
        $this->getWebRequest()->setHostInfo('https://www.example.com');
        $this->getWebRequest()->allowedHosts = ['www.example.com'];
        $this->parseRequest();

        self::assertSame('', HostAlert::make()->render());
    }

    public function testAConfiguredHostSilencesTheAlert(): void
    {
        $this->getWebRequest()->setHostInfo('https://www.example.com');
        $this->parseRequest(['hostInfo' => 'https://www.example.com']);

        self::assertSame('', HostAlert::make()->render());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function parseRequest(array $config = []): void
    {
        // The tenant bundle's subclass pins the host of the tenant it resolves; this is the skeleton's own.
        Yii::$container->clear(UrlManager::class);
        Yii::$app->set('urlManager', ['class' => UrlManager::class, ...$config]);

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);

        $manager->parseRequest($this->getWebRequest());
    }
}
