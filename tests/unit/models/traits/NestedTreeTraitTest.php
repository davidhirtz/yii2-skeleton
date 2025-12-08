<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\traits\NestedTreeTrait;
use Yii;

class NestedTreeTraitTest extends Unit
{
    protected function _before(): void
    {
        $columns = [
            'id' => 'pk',
            'name' => 'string NOT NULL',
            'parent_id' => 'integer unsigned NULL DEFAULT NULL',
            'lft' => 'integer unsigned NOT NULL',
            'rgt' => 'integer unsigned NOT NULL',
        ];

        Yii::$app->getDb()
            ->createCommand()
            ->createTable(TestNestedTreeActiveRecord::tableName(), $columns)
            ->execute();

        parent::_before();
    }

    protected function _after(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(TestNestedTreeActiveRecord::tableName())
            ->execute();

        parent::_after();
    }

    public function testNestedTree(): void
    {
        $root = TestNestedTreeActiveRecord::create();
        $root->name = 'Root';

        self::assertTrue($root->save());
        self::assertEquals(1, $root->lft);
        self::assertEquals(2, $root->rgt);
        self::assertNull($root->parent_id);

        $child = TestNestedTreeActiveRecord::create();
        $child->name = 'Child';
        $child->populateParentRelation($root);

        self::assertTrue($child->save());
        self::assertEquals(2, $child->lft);
        self::assertEquals(3, $child->rgt);
        self::assertEquals(1, $child->parent_id);

        $root->refresh();

        self::assertEquals(1, $root->lft);
        self::assertEquals(4, $root->rgt);

        self::assertCount(1, $child->getAncestors());
        self::assertCount(0, $child->getDescendants());
        self::assertArrayHasKey($root->id, $child->getAncestors());

        self::assertCount(1, $root->getDescendants());
        self::assertCount(0, $root->getAncestors());
        self::assertArrayHasKey($child->id, $root->getDescendants());

        $child->delete();
        $root->refresh();

        self::assertEquals(1, $root->lft);
        self::assertEquals(2, $root->rgt);
    }
}

class TestNestedTreeActiveRecord extends ActiveRecord
{
    use NestedTreeTrait;

    #[\Override]
    public function rules(): array
    {
        return [
            [
                ['name'],
                'required',
            ],
            [
                ['parent_id'],
                $this->validateParentId(...)
            ],
        ];
    }

    #[\Override]
    public function beforeSave($insert): bool
    {
        $this->updateTreeBeforeSave();
        return parent::beforeSave($insert);
    }

    #[\Override]
    public function afterDelete(): void
    {
        $this->updateNestedTreeAfterDelete();
        parent::afterDelete();
    }

    #[\Override]
    public static function tableName(): string
    {
        return 'test_nested_tree';
    }
}
