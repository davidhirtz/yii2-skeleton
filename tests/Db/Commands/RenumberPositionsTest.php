<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db\Commands;

use Hirtz\Skeleton\Db\Commands\RenumberPositions;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Query;

class RenumberPositionsTest extends TestCase
{
    private const string TABLE = 'renumber_positions_test';

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()->createTable(self::TABLE, [
            'id' => 'pk',
            'parent_id' => 'integer unsigned NOT NULL',
            'position' => 'integer unsigned NOT NULL DEFAULT 0',
        ])->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()->dropTable(self::TABLE)->execute();
    }

    public function testTheConditionNarrowsTheRenumberingToOneParent(): void
    {
        $this->insertRows([[1, 1, 2], [2, 1, 5], [3, 1, 9], [4, 2, 3], [5, 2, 7]]);

        self::assertSame(3, $this->renumber(['parent_id' => 1]));
        self::assertSame([1 => 1, 2 => 2, 3 => 3, 4 => 3, 5 => 7], $this->getPositions());
    }

    /**
     * Without a condition every parent is renumbered on its own, which is what a migration repairing an
     * installation runs. Equal positions keep the order of their ids.
     */
    public function testEveryParentIsRenumberedOnItsOwn(): void
    {
        $this->insertRows([[1, 1, 4], [2, 1, 4], [3, 1, 1], [4, 2, 0], [5, 2, 8]]);

        $this->renumber();
        self::assertSame([1 => 2, 2 => 3, 3 => 1, 4 => 1, 5 => 2], $this->getPositions());

        self::assertSame(0, $this->renumber());
    }

    /**
     * @param array<string, mixed> $condition
     */
    private function renumber(array $condition = []): int
    {
        return (new RenumberPositions(Yii::$app->getDb(), self::TABLE, ['parent_id'], $condition))->execute();
    }

    /**
     * @param list<array{int, int, int}> $rows the id, parent and position of each row
     */
    private function insertRows(array $rows): void
    {
        Yii::$app->getDb()->createCommand()
            ->batchInsert(self::TABLE, ['id', 'parent_id', 'position'], $rows)
            ->execute();
    }

    /**
     * @return array<int, int>
     */
    private function getPositions(): array
    {
        $rows = (new Query())->select(['position', 'id'])->from(self::TABLE)->orderBy(['id' => SORT_ASC])->all();

        return array_combine(
            array_map(intval(...), array_column($rows, 'id')),
            array_map(intval(...), array_column($rows, 'position')),
        );
    }
}
