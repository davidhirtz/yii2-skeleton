<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\traits\MaterializedTreeTrait;
use Yii;
use yii\db\Expression;

class MaterializedTreeTraitTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $columns = [
            'id' => 'pk',
            'parent_id' => 'integer null',
            'path' => 'string null',
            'name' => 'string null',
            'position' => 'integer',
        ];

        Yii::$app->getDb()->createCommand()
            ->createTable(TestMaterializedTreeActiveRecord::tableName(), $columns)
            ->execute();

        $rows = [
            [
                'id' => 1,
                'parent_id' => null,
                'path' => null,
                'name' => 'Root 1',
                'position' => 1,
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'path' => '1',
                'name' => 'Child of 1 – 1',
                'position' => 1,
            ],
            [
                'id' => 3,
                'parent_id' => 1,
                'path' => '1',
                'name' => 'Child of 1 – 2',
                'position' => 2,
            ],
            [
                'id' => 4,
                'parent_id' => 2,
                'path' => '1,2',
                'name' => 'Child of 2 – 1',
                'position' => 1,
            ],
            [
                'id' => 5,
                'parent_id' => 2,
                'path' => '1,2',
                'name' => 'Child of 2 – 2',
                'position' => 2,
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'path' => null,
                'name' => 'Root 2',
                'position' => 2,
            ],
            [
                'id' => 7,
                'parent_id' => null,
                'path' => null,
                'name' => 'Root 3',
                'position' => 3,
            ],
        ];


        Yii::$app->getDb()->createCommand()
            ->batchInsert(TestMaterializedTreeActiveRecord::tableName(), array_keys($rows[0]), $rows)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(TestMaterializedTreeActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testAncestors(): void
    {
        $model = TestMaterializedTreeActiveRecord::findOne(1);
        self::assertEmpty($model->getAncestors());

        $model = TestMaterializedTreeActiveRecord::findOne(4);
        $ancestors = $model->getAncestors(true);

        self::assertAncestors($ancestors);

        $models = $this->getAllModelsInRandomOrder();

        $model->setAncestors($models);
        self::assertAncestors($model->ancestors);

        self::assertEquals(1, $model->ancestors[2]->getFirstAncestor()->id);
    }

    private static function assertAncestors(array $ancestors): void
    {
        self::assertArrayHasKey(1, $ancestors);
        self::assertArrayHasKey(2, $ancestors);

        self::assertCount(2, $ancestors);
    }

    public function testChildren(): void
    {
        $model = TestMaterializedTreeActiveRecord::findOne(1);
        $children = $model->getChildren(true);

        self::assertChildren($children);

        $models = $this->getAllModelsInRandomOrder();
        $model->setChildren($models);

        self::assertChildren($model->children);
    }

    private static function assertChildren(array $children): void
    {
        self::assertArrayHasKey(2, $children);
        self::assertArrayHasKey(3, $children);

        self::assertCount(2, $children);
    }

    public function testDescendants(): void
    {
        $model = TestMaterializedTreeActiveRecord::findOne(1);
        $descendants = $model->getDescendants(true);

        self::assertDescendants($descendants);

        $models = $this->getAllModelsInRandomOrder();
        $model->setDescendants($models);

        self::assertDescendants($descendants);
    }

    private static function assertDescendants(array $descendants): void
    {
        self::assertArrayHasKey(2, $descendants);
        self::assertArrayHasKey(3, $descendants);
        self::assertArrayHasKey(4, $descendants);
        self::assertArrayHasKey(5, $descendants);

        self::assertCount(4, $descendants);
    }

    public function testParent(): void
    {
        $model = TestMaterializedTreeActiveRecord::findOne(4);
        self::assertEquals(2, $model->getParent()->select('id')->scalar());
        self::assertEquals(2, $model->parent['id']);
    }

    public function testSiblings(): void
    {
        $model = TestMaterializedTreeActiveRecord::findOne(2);
        self::assertEquals(2, $model->findSiblings()->count());
    }

    private function getAllModelsInRandomOrder(): array
    {
        return TestMaterializedTreeActiveRecord::find()
            ->orderBy([new Expression('RAND()')])
            ->all();
    }
}

/**
 * @property int $id
 * @property int $parent_id
 * @property string $path
 * @property string|null $name
 * @property int $position
 */
class TestMaterializedTreeActiveRecord extends ActiveRecord
{
    use MaterializedTreeTrait;

    #[\Override]
    public static function tableName(): string
    {
        return '{{%test_materialized_tree}}';
    }
}
