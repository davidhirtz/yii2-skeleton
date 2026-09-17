<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Base;

use Hirtz\Skeleton\Log\SentryTarget;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Sentry\SentrySdk;
use Yii;
use yii\log\FileTarget;
use yii\log\Logger;

/**
 * `params.sentryDsn` is the whole of the wiring: an installation without it builds no Sentry client at all, and
 * one with it keeps the file log beside the reports.
 */
class SentryConfigurationTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        SentrySdk::init();
        parent::tearDown();
    }

    public function testNoTargetWithoutTheParameter(): void
    {
        self::assertArrayNotHasKey('sentry', Yii::$app->getLog()->targets);
        self::assertInstanceOf(FileTarget::class, Yii::$app->getLog()->targets['file']);
    }

    public function testTheParameterAddsATargetBesideTheFileOne(): void
    {
        $this->config['params']['sentryDsn'] = 'https://public@sentry.localhost/1';
        $this->reloadApplication();

        $targets = Yii::$app->getLog()->targets;
        $target = $targets['sentry'] ?? null;

        self::assertInstanceOf(SentryTarget::class, $target);
        self::assertInstanceOf(FileTarget::class, $targets['file']);

        self::assertSame('https://public@sentry.localhost/1', $target->dsn);
        self::assertSame(['yii\web\HttpException:4*'], $target->except);

        // A page nobody asked for is not an error worth a report; the 5xx above it is.
        $target->collect([
            ['Not found', Logger::LEVEL_ERROR, 'yii\web\HttpException:404', 0.0, [], 0],
            ['Server error', Logger::LEVEL_ERROR, 'yii\web\HttpException:500', 0.0, [], 0],
            ['Just so you know', Logger::LEVEL_INFO, 'application', 0.0, [], 0],
        ], false);

        self::assertSame(['Server error'], array_column($target->messages, 0));
    }
}
