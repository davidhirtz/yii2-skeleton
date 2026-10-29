<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Caching;

use Yii;
use yii\mutex\Mutex;

/**
 * The cache has no atomic increment, so the `mutex` component makes the read and the write one step.
 */
final class CacheCounter
{
    public static function get(mixed $key): int
    {
        return (int)Yii::$app->getCache()->get($key);
    }

    /**
     * Restarts the counter's lifetime.
     */
    public static function increment(mixed $key, int $duration): int
    {
        $cache = Yii::$app->getCache();
        $mutex = Yii::$app->has('mutex') ? Yii::$app->get('mutex') : null;
        $name = 'cache-counter-' . md5(serialize($key));

        // One that cannot be locked in time is still counted: a lost increment beats an uncounted guess
        $isLocked = $mutex instanceof Mutex && $mutex->acquire($name, 5);

        try {
            $count = (int)$cache->get($key) + 1;
            $cache->set($key, $count, $duration);
        } finally {
            if ($isLocked) {
                $mutex->release($name);
            }
        }

        return $count;
    }
}
