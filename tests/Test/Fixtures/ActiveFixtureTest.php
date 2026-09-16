<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Test\Fixtures;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\TrailFixture;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Expression;

class ActiveFixtureTest extends TestCase
{
    private const int FOREIGN_ROW_ID = 999;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function fixtures(): array
    {
        return [
            'user' => UserFixture::class,
            'trail' => TrailFixture::class,
        ];
    }

    /**
     * A test losing its transaction to DDL runs this delete outside one, so a full table reset would commit the
     * removal of rows nothing in the suite created — the seeded tenant among them (monorepo #142).
     */
    public function testUnloadOnlyRemovesTheLoadedRows(): void
    {
        $this->insertForeignRow();

        $fixture = $this->getTrailFixture();
        self::assertNotEmpty($fixture->data);

        $fixture->unload();

        self::assertSame([self::FOREIGN_ROW_ID], $this->findTrailIds());
    }

    /**
     * `initFixtures()` unloads before it loads, which is what clears whatever an interrupted run left behind.
     */
    public function testUnloadWithoutLoadedRowsClearsTheTable(): void
    {
        $this->insertForeignRow();

        $fixture = $this->getTrailFixture();
        $fixture->unload();
        $fixture->unload();

        self::assertSame([], $this->findTrailIds());
    }

    private function insertForeignRow(): void
    {
        Yii::$app->getDb()->createCommand()
            ->insert(Trail::tableName(), [
                'id' => self::FOREIGN_ROW_ID,
                'type' => Trail::TYPE_CREATE,
                'model_class' => User::class,
                'model_id' => '1',
                'created_at' => new Expression('UTC_TIMESTAMP()'),
            ])
            ->execute();
    }

    private function getTrailFixture(): TrailFixture
    {
        /** @var TrailFixture $fixture */
        $fixture = $this->getFixture('trail');
        return $fixture;
    }

    /**
     * @return list<int>
     */
    private function findTrailIds(): array
    {
        return array_values(array_map(intval(...), Trail::find()->select('id')->column()));
    }
}
