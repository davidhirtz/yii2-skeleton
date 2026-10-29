<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Db\Dsn;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\db\Query;

class BaselineCharsetTest extends TestCase
{
    /**
     * A baseline copying v2's `utf8mb3` would reject an emoji in any text a user types.
     */
    public function testEveryBaselineTableIsUtf8mb4(): void
    {
        $db = Yii::$app->getDb();

        $tables = (new Query())
            ->select('TABLE_NAME')
            ->from('information_schema.TABLES')
            ->where([
                'TABLE_SCHEMA' => Dsn::fromString($db->dsn)->database,
                'TABLE_TYPE' => 'BASE TABLE',
            ])
            ->andWhere(['not like', 'TABLE_COLLATION', 'utf8mb4%', false])
            ->column($db);

        self::assertSame([], $tables);
    }
}
