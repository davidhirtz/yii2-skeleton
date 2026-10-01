<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\db\IndexConstraint;

class RedirectUrlIndexTest extends TestCase
{
    public function testTheRedirectTargetIsIndexed(): void
    {
        $indexes = Yii::$app->getDb()->getSchema()->getTableIndexes('{{%redirect}}', true);
        $columns = array_map(fn (IndexConstraint $index): array => (array)$index->columnNames, $indexes);

        self::assertContains(['url'], $columns);
    }
}
