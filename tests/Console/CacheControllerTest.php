<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\CacheController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class CacheControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testActionFlushTakesTheCacheNames(): void
    {
        self::assertSame(CacheController::class, Application::current()->controllerMap['cache']);
        Application::current()->controllerMap['cache'] = CacheControllerMock::class;

        Yii::$app->getCache()?->set('key', 'value');

        self::assertSame(0, Application::current()->runAction('cache/flush', ['cache', 'interactive' => '0']));
        self::assertFalse(Yii::$app->getCache()?->get('key'));
    }
}

class CacheControllerMock extends CacheController
{
    use StdOutBufferControllerTrait;
}
