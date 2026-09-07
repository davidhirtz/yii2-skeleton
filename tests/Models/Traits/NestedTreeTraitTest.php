<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Traits\NestedTreeTrait;
use Override;
use Yii;

class NestedTreeTraitTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $columns = [
            'id' => 'pk',
            'name' => 'string NOT NULL',
            'parent_id' => 'integer unsigned NULL DEFAULT NULL',
            'lft' => 'integer unsigned NOT NULL',
            'rgt' => 'integer unsigned NOT NULL',
            'depth' => 'integer unsigned NOT NULL DEFAULT 0',
            'position' => 'integer unsigned NOT NULL DEFAULT 0',
        ];

        Yii::$app->getDb()
            ->createCommand()
            ->createTable(TestNestedTreeActiveRecord::tableName(), $columns)
            ->execute();
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(TestNestedTreeActiveRecord::tableName())
            ->execute();

        parent::tearDown();
    }

    public function testNestedTree(): void
    {
        $root = TestNestedTreeActiveRecord::create();
        $root->name = 'Root';

        self::assertTrue($root->save());
        self::assertEquals(1, $root->lft);
        self::assertEquals(2, $root->rgt);
        self::assertEquals(0, $root->depth);
        self::assertNull($root->parent_id);

        $child = TestNestedTreeActiveRecord::create();
        $child->name = 'Child';
        $child->populateParentRelation($root);

        self::assertTrue($child->save());
        self::assertEquals(2, $child->lft);
        self::assertEquals(3, $child->rgt);
        self::assertEquals(1, $child->depth);
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

    public function testDepthIsUpdatedWhenMovingBranch(): void
    {
        $root = TestNestedTreeActiveRecord::create();
        $root->name = 'Root';
        $root->save();

        $branch = TestNestedTreeActiveRecord::create();
        $branch->name = 'Branch';
        $branch->populateParentRelation($root);
        $branch->save();

        $leaf = TestNestedTreeActiveRecord::create();
        $leaf->name = 'Leaf';
        $leaf->populateParentRelation($branch);
        $leaf->save();

        self::assertEquals(1, $branch->depth);
        self::assertEquals(2, $leaf->depth);

        // Move the whole branch to the root: its own depth and its descendant's depth drop by one.
        $branch->populateParentRelation(null);
        $branch->save();

        self::assertEquals(0, $branch->depth);

        $leaf->refresh();
        self::assertEquals(1, $leaf->depth);
    }

    public function testRebuildNestedTreeReturnsUpdatedRowCount(): void
    {
        $root = TestNestedTreeActiveRecord::create();
        $root->name = 'Root';
        $root->save();

        $first = TestNestedTreeActiveRecord::create();
        $first->name = 'First';
        $first->populateParentRelation($root);
        $first->save();

        $second = TestNestedTreeActiveRecord::create();
        $second->name = 'Second';
        $second->populateParentRelation($root);
        $second->save();

        $root->refresh();

        // Swapping the two children moves both of them.
        $order = [$second->id => 0, $first->id => 1];
        self::assertSame(2, TestNestedTreeActiveRecord::rebuildNestedTree($root, $order));

        $first->refresh();
        $second->refresh();
        self::assertLessThan($first->lft, $second->lft);

        // Re-applying the same order is a no-op and updates nothing.
        self::assertSame(0, TestNestedTreeActiveRecord::rebuildNestedTree($root, $order));
    }
}

class TestNestedTreeActiveRecord extends ActiveRecord
{
    use NestedTreeTrait;

    #[Override]
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

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->updateTreeBeforeSave();
        return parent::beforeSave($insert);
    }

    #[Override]
    public function afterDelete(): void
    {
        $this->updateNestedTreeAfterDelete();
        parent::afterDelete();
    }

    #[Override]
    public static function tableName(): string
    {
        return 'test_nested_tree';
    }
}
