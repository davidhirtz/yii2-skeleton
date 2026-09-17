<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets;

use Hirtz\Skeleton\Log\SentryTarget;
use Hirtz\Skeleton\Modules\Admin\Widgets\SentryAlert;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Sentry\SentrySdk;
use Yii;

class SentryAlertTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        SentrySdk::init();
        parent::tearDown();
    }

    public function testTheAlertIsInvisibleOnANonProductionHost(): void
    {
        $alert = SentryAlert::make();

        self::assertSame('', $alert->render());
        self::assertFalse($alert->isVisible());
        self::assertFalse($alert->getUnreported());
    }

    public function testAProductionHostWithoutATargetIsWarnedAbout(): void
    {
        $this->getWebRequest()->setHostInfo('https://www.example.com');

        $alert = SentryAlert::make();
        $html = $alert->render();

        self::assertTrue($alert->getUnreported());
        self::assertStringContainsString('data-alert="warning"', $html);
        self::assertStringContainsString('only written to its log file', $html);
    }

    public function testTheConfiguredTargetSilencesTheAlert(): void
    {
        $this->config['params']['sentryDsn'] = 'https://public@sentry.localhost/1';
        $this->reloadApplication();

        $this->getWebRequest()->setHostInfo('https://www.example.com');

        self::assertSame('', SentryAlert::make()->render());
    }

    public function testADisabledTargetDoesNotCountAsErrorReporting(): void
    {
        $this->config['params']['sentryDsn'] = 'https://public@sentry.localhost/1';
        $this->reloadApplication();

        $this->getWebRequest()->setHostInfo('https://www.example.com');
        $target = Yii::$app->getLog()->targets['sentry'];
        self::assertInstanceOf(SentryTarget::class, $target);

        $target->enabled = false;

        self::assertStringContainsString('data-alert="warning"', SentryAlert::make()->render());
    }

    public function testAProjectReportingItsErrorsElsewhereOptsOut(): void
    {
        $this->getWebRequest()->setHostInfo('https://www.example.com');

        self::assertSame('', SentryAlert::make()->unreported(false)->render());
    }
}
