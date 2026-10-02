<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Caching;

use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\mutex\Mutex;

class CacheCounterTest extends TestCase
{
    public function testEveryIncrementIsCountedAndTheLockReleased(): void
    {
        $key = ['zz-counter', 'test'];

        self::assertSame(0, CacheCounter::get($key));
        self::assertSame(1, CacheCounter::increment($key, 60));
        self::assertSame(2, CacheCounter::increment($key, 60));
        self::assertSame(2, CacheCounter::get($key));

        $mutex = Yii::$app->get('mutex');
        self::assertInstanceOf(Mutex::class, $mutex);

        $name = 'cache-counter-' . md5(serialize($key));
        self::assertTrue($mutex->acquire($name));
        self::assertTrue($mutex->release($name));

        Yii::$app->getCache()->delete($key);
    }
}
