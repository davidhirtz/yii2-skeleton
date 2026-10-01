<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Test\TestCase;
use Yii;

class UserIsOwnerNotNullTest extends TestCase
{
    public function testTheOwnerFlagIsNeverNull(): void
    {
        $column = Yii::$app->getDb()->getTableSchema('{{%user}}', true)?->getColumn('is_owner');

        self::assertNotNull($column);
        self::assertFalse($column->allowNull);
        self::assertSame(0, (int)$column->defaultValue);
    }
}
