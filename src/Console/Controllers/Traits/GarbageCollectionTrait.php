<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers\Traits;

use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Yii;

trait GarbageCollectionTrait
{
    /**
     * @var int The number of seconds to sleep between batches when deleting records to prevent a database shutdown
     * while deleting huge amounts of records, defaults to 1 second.
     */
    public int $sleep = 1;

    /**
     * Orders by primary key and deletes in batches, so the table is never locked for long and no `WHERE` clause
     * grows unbounded. This means the id must be an auto-incrementing integer, with the oldest records having the
     * lowest ids.
     *
     * @param class-string<ActiveRecord> $modelClass
     * @return int the number of records deleted
     */
    protected function deleteExpiredRecords(string $modelClass, int $lifetime, int $limit = 100): int
    {
        $threshold = gmdate('Y-m-d H:i:s', time() - $lifetime);
        $totalCount = 0;

        $query = $modelClass::find()
            ->select(['id', 'created_at'])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->asArray();

        while (true) {
            $rows = $query->all();
            $ids = [];

            foreach ($rows as $row) {
                if ($row['created_at'] < $threshold) {
                    $ids[] = $row['id'];
                }
            }

            if ($ids) {
                $deletedCount = $modelClass::deleteAll(['id' => $ids]);
                $totalCount += $deletedCount;

                $count = Yii::$app->getFormatter()->asInteger($totalCount);
                $this->stdout("Deleting records ... ($count)\n");

                if ($deletedCount === count($rows)) {
                    if ($this->sleep && count($rows) === $limit) {
                        sleep($this->sleep);
                    }

                    continue;
                }
            }

            break;
        }

        return $totalCount;
    }

    protected function optimizeTable(string $tableName): void
    {
        $db = Yii::$app->getDb();
        $name = $db->getSchema()->getRawTableName($tableName);

        $this->interactiveStartStdout("Optimizing $name table ... ");
        $success = false;

        try {
            $db->createCommand('OPTIMIZE TABLE ' . $tableName);
            $success = true;
        } catch (Exception $exception) {
            Yii::error($exception->getMessage());
        }

        $this->interactiveDoneStdout($success);
    }
}
