<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Commands;

use Hirtz\Skeleton\Db\ActiveRecord;
use Yii;
use yii\base\NotSupportedException;
use yii\db\Command;

/**
 * @see ActiveRecord::batchInsert()
 */
class BatchInsertQueryBuild
{
    public readonly Command $command;

    /**
     * @param class-string<ActiveRecord> $modelClass
     * @param list<string>|list<array<string, mixed>> $columns the column names, or the rows when `$rows` is omitted
     * @param list<array<string, mixed>>|null $rows the rows to be batch-inserted into the table
     * @param bool $ignore whether records should be inserted regardless of previous errors or existing primary keys
     */
    public function __construct(string $modelClass, array $columns, ?array $rows = null, bool $ignore = false)
    {
        if ($rows === null) {
            $rows = $columns;
            $first = current($columns);
            $columns = is_array($first) ? array_keys($first) : [];
        }

        $db = $modelClass::getDb();
        $command = $db->createCommand()->batchInsert($modelClass::tableName(), $columns, $rows);

        if ($ignore) {
            if ($db->getDriverName() !== 'mysql') {
                throw new NotSupportedException($modelClass . '::batchInsert does not support the option `ignore` for this database driver.');
            }

            $sql = $command->getRawSql();
            $sql = 'INSERT IGNORE' . mb_substr($sql, strlen('INSERT'), null, Yii::$app->charset);

            $command = $db->createCommand($sql);
        }

        $this->command = $command;
    }
}
