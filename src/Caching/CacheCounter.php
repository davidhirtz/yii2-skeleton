<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Caching;

use Yii;
use yii\mutex\Mutex;

/**
 * A counter in the cache, which has no increment of its own: concurrent requests would each write back the count
 * they read, losing every increment but one, and a burst of guesses would count as one. The `mutex` component makes
 * the read and the write one step.
 */
final class CacheCounter
{
    public static function get(mixed $key): int
    {
        return (int)Yii::$app->getCache()->get($key);
    }

    /**
     * Adds one and restarts the counter's lifetime.
     *
     * @return int the count after the increment
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
