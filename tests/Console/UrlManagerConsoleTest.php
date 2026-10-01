<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\UrlManager;
use Yii;

class UrlManagerConsoleTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testThePinnedHostInfoBuildsAbsoluteUrls(): void
    {
        Yii::$app->params['hostInfo'] = 'https://www.example.com';
        $urlManager = Yii::createObject(UrlManager::class);

        self::assertSame('https://www.example.com/admin/account/reset?token=x', $urlManager->createAbsoluteUrl([
            '/admin/account/reset',
            'token' => 'x',
        ]));
    }

    public function testAConfiguredBaseUrlIsKept(): void
    {
        Yii::$app->params['hostInfo'] = 'https://www.example.com';
        $urlManager = Yii::createObject(UrlManager::class, [['baseUrl' => '/site']]);

        self::assertSame('/site', $urlManager->getBaseUrl());
    }
}
