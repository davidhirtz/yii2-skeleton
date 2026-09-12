<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\TrailFixture;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use yii\base\InvalidCallException;
use yii\db\ActiveQuery;

/**
 * `selectWith()` reads a hasOne record off the joined row, where `joinWith()` queries it again. Every trail of the
 * fixture belongs to the owner.
 */
class ActiveQuerySelectWithTest extends TestCase
{
    #[Override]
    public function fixtures(): array
    {
        return [
            'user' => UserFixture::class,
            'trail' => TrailFixture::class,
        ];
    }

    public function testPopulatesTheRelationFromTheSameRow(): void
    {
        $trails = [];

        $count = $this->countQueries(function () use (&$trails): void {
            $trails = Trail::find()->selectWith('user')->all();
        });

        self::assertSame(1, $count);
        self::assertCount($this->getTrailCount(), $trails);

        foreach ($trails as $trail) {
            self::assertTrue($trail->isRelationPopulated('user'));
            self::assertSame($trail->user_id, $trail->user->id);
        }

        // One related row, one object: the trails of a user share it, as they would after `with()`.
        self::assertSame($trails[0]->user, $trails[1]->user);
    }

    public function testCallbackConditionsTheJoinAndAMissPopulatesNull(): void
    {
        $disabled = $this->getTrailId(1);
        $enabled = $this->getTrailId(0);
        Trail::updateAll(['user_id' => 2], ['id' => $disabled]);

        $trails = Trail::find()
            ->selectWith('user', callback: fn (ActiveQuery $query) => $query->onCondition([
                '{{user}}.[[status]]' => User::STATUS_ENABLED,
            ]))
            ->andWhere([Trail::tableName() . '.[[id]]' => [$enabled, $disabled]])
            ->indexBy('id')
            ->all();

        // The condition lives in the ON clause, so the LEFT JOIN keeps the row it excludes.
        self::assertCount(2, $trails);
        self::assertSame(1, $trails[$enabled]->user?->id);

        self::assertTrue($trails[$disabled]->isRelationPopulated('user'));
        self::assertNull($trails[$disabled]->user);
        self::assertSame(0, $this->countQueries(fn () => $trails[$disabled]->user));
    }

    public function testInnerJoinFiltersTheRows(): void
    {
        Trail::updateAll(['user_id' => null], ['id' => $this->getTrailId(0)]);

        $trails = Trail::find()->selectWith('user', 'INNER JOIN')->all();

        self::assertCount($this->getTrailCount() - 1, $trails);
    }

    /**
     * The joined table has an `id` too; the query's own select has to survive next to the joined columns.
     */
    public function testOwnSelectSurvivesTheJoinedColumns(): void
    {
        $id = $this->getTrailId(2);

        $trail = Trail::find()
            ->select([Trail::tableName() . '.[[id]]', Trail::tableName() . '.[[user_id]]'])
            ->selectWith('user')
            ->andWhere([Trail::tableName() . '.[[id]]' => $id])
            ->one();

        self::assertSame($id, $trail->id);
        self::assertSame(1, $trail->user->id);
    }

    public function testSelectAfterwardsLeavesTheRelationLazy(): void
    {
        $trail = Trail::find()
            ->selectWith('user')
            ->select([Trail::tableName() . '.*'])
            ->andWhere([Trail::tableName() . '.[[id]]' => $this->getTrailId(0)])
            ->one();

        self::assertFalse($trail->isRelationPopulated('user'));
        self::assertSame(1, $trail->user->id);
    }

    public function testEachPopulatesEveryBatch(): void
    {
        $count = 0;

        foreach (Trail::find()->selectWith('user')->each(2) as $trail) {
            self::assertTrue($trail->isRelationPopulated('user'));
            $count++;
        }

        self::assertSame($this->getTrailCount(), $count);
    }

    public function testAsArrayKeepsThePrefixedColumns(): void
    {
        $row = Trail::find()
            ->selectWith('user')
            ->asArray()
            ->andWhere([Trail::tableName() . '.[[id]]' => $this->getTrailId(0)])
            ->one();

        self::assertSame(1, (int)$row['user__id']);
    }

    public function testHasManyIsRefused(): void
    {
        $this->expectException(InvalidCallException::class);
        User::find()->selectWith('authClients');
    }

    protected function getTrailId(int $index): int
    {
        return (int)array_values($this->getTrailFixture()->data)[$index]['id'];
    }

    protected function getTrailCount(): int
    {
        return count($this->getTrailFixture()->data);
    }

    protected function getTrailFixture(): TrailFixture
    {
        /** @var TrailFixture $fixture */
        $fixture = $this->getFixture('trail');
        return $fixture;
    }
}
