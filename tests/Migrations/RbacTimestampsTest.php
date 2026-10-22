<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Migrations\M261022120000RbacTimestamps;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class RbacTimestampsTest extends TestCase
{
    private const array COLUMNS = [
        'auth_rule' => ['created_at', 'updated_at'],
        'auth_item' => ['updated_at', 'created_at'],
        'auth_assignment' => ['created_at'],
    ];

    public function testAnUpgradedDatabaseGetsTheBaselinesBigint(): void
    {
        $db = Yii::$app->getDb();

        try {
            $db->createCommand('ALTER TABLE {{%auth_rule}} MODIFY [[created_at]] int(11) DEFAULT NULL, MODIFY [[updated_at]] int(11) DEFAULT NULL')->execute();
            $db->createCommand('ALTER TABLE {{%auth_item}} MODIFY [[updated_at]] int(11) DEFAULT NULL, MODIFY [[created_at]] int(11) NOT NULL')->execute();
            $db->createCommand('ALTER TABLE {{%auth_assignment}} MODIFY [[created_at]] int(11) DEFAULT NULL')->execute();
        } finally {
            $this->migrate();
        }

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $name) {
                self::assertSame('bigint(20)', $db->getTableSchema("{{%$table}}", true)?->getColumn($name)?->dbType);
            }
        }

        self::assertFalse($db->getTableSchema('{{%auth_item}}')?->getColumn('created_at')?->allowNull);
        self::assertTrue($db->getTableSchema('{{%auth_rule}}')?->getColumn('created_at')?->allowNull);
    }

    private function migrate(): void
    {
        ob_start();

        try {
            (new M261022120000RbacTimestamps())->safeUp();
        } finally {
            ob_end_clean();
        }
    }
}
