<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * Widens the RBAC timestamps of a database upgraded from v2 to the `bigint` the baseline creates.
 *
 * @noinspection PhpUnused
 */
class M261022120000RbacTimestamps extends Migration
{
    private const array COLUMNS = [
        ['{{%auth_rule}}', 'created_at', 'bigint(20) DEFAULT NULL'],
        ['{{%auth_rule}}', 'updated_at', 'bigint(20) DEFAULT NULL'],
        ['{{%auth_item}}', 'updated_at', 'bigint(20) DEFAULT NULL'],
        ['{{%auth_item}}', 'created_at', 'bigint(20) NOT NULL'],
        ['{{%auth_assignment}}', 'created_at', 'bigint(20) DEFAULT NULL'],
    ];

    #[Override]
    public function safeUp(): void
    {
        foreach (self::COLUMNS as [$table, $name, $definition]) {
            $column = $this->getDb()->getTableSchema($table, true)?->getColumn($name);

            if ($column !== null && !str_starts_with($column->dbType, 'bigint')) {
                $this->alterColumn($table, $name, $definition);
            }
        }
    }

    #[Override]
    public function safeDown(): void
    {
    }
}
