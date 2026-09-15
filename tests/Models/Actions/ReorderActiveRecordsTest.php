<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Actions;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Actions\ReorderActiveRecords;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use RuntimeException;
use Yii;

class ReorderActiveRecordsTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(ReorderRecord::tableName(), [
                'id' => 'pk',
                'position' => 'int not null default 0',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(ReorderRecord::tableName())
            ->execute();
    }

    public function testRunUpdatesPositions(): void
    {
        $ids = $this->createRecords();
        $order = array_reverse($ids);

        $action = new ReorderActiveRecords(ReorderRecord::find()->all(), array_flip($order));

        self::assertSame(2, $action->run());
        self::assertSame($order, $this->getOrderedIds());
    }

    public function testFailingAfterReorderRollsBackThePositions(): void
    {
        $ids = $this->createRecords();

        $action = new class (ReorderRecord::find()->all(), array_flip(array_reverse($ids))) extends ReorderActiveRecords {
            #[Override]
            protected function afterReorder(): void
            {
                throw new RuntimeException('Nope');
            }
        };

        $this->expectException(RuntimeException::class);

        try {
            $action->run();
        } finally {
            self::assertSame($ids, $this->getOrderedIds());
        }
    }

    /**
     * @return list<int>
     */
    private function createRecords(): array
    {
        $ids = [];

        foreach ([1, 2] as $position) {
            $record = new ReorderRecord();
            $record->position = $position;
            $record->insert();

            $ids[] = $record->id;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function getOrderedIds(): array
    {
        return array_values(array_map(intval(...), ReorderRecord::find()
            ->select(['id'])
            ->orderBy(['position' => SORT_ASC])
            ->column()));
    }
}

/**
 * @property int $id
 * @property int $position
 */
class ReorderRecord extends ActiveRecord
{
    #[Override]
    public static function tableName(): string
    {
        return '{{%test_reorder}}';
    }
}
