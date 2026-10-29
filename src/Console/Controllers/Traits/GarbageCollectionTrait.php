<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers\Traits;

use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Yii;
use yii\console\ExitCode;

trait GarbageCollectionTrait
{
    /**
     * @var int the seconds to sleep between batches, so deleting huge amounts of records does not shut the database down
     */
    public int $sleep = 1;

    /**
     * @var int the number of records deleted per batch
     */
    public int $batchSize = 100;

    /**
     * Deletes in primary-key batches, so the id must be an auto-incrementing integer with the oldest records lowest.
     *
     * @param class-string<ActiveRecord> $modelClass
     */
    protected function deleteExpiredRecords(string $modelClass, int $lifetime, ?int $limit = null): int
    {
        $limit = max(1, $limit ?? $this->batchSize);
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

                // Progress only for a terminal: a cron job's mail would get a line per batch
                if ($this->interactive) {
                    $count = Yii::$app->getFormatter()->asInteger($totalCount);
                    $this->stdout("Deleting records ... ($count)\n");
                }

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

    protected function optimizeTable(string $tableName): int
    {
        $db = Yii::$app->getDb();
        $name = $db->getSchema()->getRawTableName($tableName);

        $this->interactiveStartStdout("Optimizing $name table ... ");
        $success = false;

        try {
            // MySQL reports a failure as a result row rather than an error
            $rows = $db->createCommand('OPTIMIZE TABLE ' . $db->quoteTableName($tableName))->queryAll();
            $success = !array_filter($rows, fn (array $row): bool => strtolower((string)($row['Msg_type'] ?? '')) === 'error');
        } catch (Exception $exception) {
            Yii::error($exception->getMessage());
        }

        $this->interactiveDoneStdout($success);

        if (!$success) {
            $this->stderr("Optimizing the $name table failed." . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
