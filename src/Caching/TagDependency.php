<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Caching;

use Override;
use WeakMap;
use Yii;
use yii\base\Event;
use yii\caching\CacheInterface;
use yii\db\Connection;

/**
 * Invalidates again after the outermost commit: a request between a save and its commit caches the old content
 * under the fresh tags.
 */
class TagDependency extends \yii\caching\TagDependency
{
    /**
     * @var WeakMap<Connection, array<string, array{CacheInterface, string|string[]}>>|null
     */
    private static ?WeakMap $pending = null;

    /**
     * @param CacheInterface $cache
     * @param string|string[] $tags
     */
    #[Override]
    public static function invalidate($cache, $tags): void
    {
        parent::invalidate($cache, $tags);

        $db = Yii::$app->getDb();

        if (!$db->getTransaction()) {
            return;
        }

        self::$pending ??= new WeakMap();

        if (!isset(self::$pending[$db])) {
            self::$pending[$db] = [];

            $settle = static function (Event $event) use ($db, &$settle): void {
                $db->off(Connection::EVENT_COMMIT_TRANSACTION, $settle);
                $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, $settle);

                $pending = self::$pending[$db] ?? [];
                unset(self::$pending[$db]);

                if ($event->name === Connection::EVENT_COMMIT_TRANSACTION) {
                    foreach ($pending as [$cache, $tags]) {
                        parent::invalidate($cache, $tags);
                    }
                }
            };

            $db->on(Connection::EVENT_COMMIT_TRANSACTION, $settle);
            $db->on(Connection::EVENT_ROLLBACK_TRANSACTION, $settle);
        }

        $key = spl_object_id($cache) . ':' . implode("\0", (array)$tags);
        self::$pending[$db] = [...self::$pending[$db], $key => [$cache, $tags]];
    }
}
