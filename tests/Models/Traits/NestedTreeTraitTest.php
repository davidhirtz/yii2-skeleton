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
    protected function setUpSchema(): void
    {
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
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(TestNestedTreeActiveRecord::tableName())
            ->execute();
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

    public function testValidateParentIdRefusesAParentThatDoesNotExist(): void
    {
        $record = TestNestedTreeActiveRecord::create();
        $record->name = 'Orphan';
        $record->parent_id = 99999;

        self::assertFalse($record->save());
        self::assertArrayHasKey('parent_id', $record->getErrors());
    }

    public function testValidateParentIdRefusesTheRecordItself(): void
    {
        $root = $this->createRecord('Root');

        $root->parent_id = $root->id;

        self::assertFalse($root->save());
        self::assertArrayHasKey('parent_id', $root->getErrors());
    }

    /**
     * A record cannot be moved below one of its own descendants: the branch would be detached from the tree.
     */
    public function testValidateParentIdRefusesADescendant(): void
    {
        $root = $this->createRecord('Root');
        $child = $this->createRecord('Child', $root);

        $root->parent_id = $child->id;

        self::assertFalse($root->save());
        self::assertArrayHasKey('parent_id', $root->getErrors());
    }

    public function testAParentIdThatDidNotChangeIsNotLookedUpAgain(): void
    {
        $root = $this->createRecord('Root');
        $child = $this->createRecord('Child', $root);

        $child->name = 'Renamed';

        self::assertTrue($child->save());
        self::assertSame($root->id, $child->parent_id);
    }

    public function testGetFirstAncestorIsTheOutermostOne(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $leaf = $this->createRecord('Leaf', $branch);

        self::assertSame($root->id, $leaf->getFirstAncestor()?->id);
        self::assertNull($root->getFirstAncestor());
    }

    /**
     * `setAncestors()` and `setDescendants()` fill the caches from one already loaded, `lft`-ordered set, so a grid
     * does not query per row.
     */
    public function testTheAncestorAndDescendantCachesAreFilledFromALoadedSet(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $leaf = $this->createRecord('Leaf', $branch);

        $records = TestNestedTreeActiveRecord::find()
            ->orderBy(['lft' => SORT_ASC])
            ->all();

        [$root, $branch, $leaf] = $records;

        $queries = $this->countQueries(function () use ($records, $root, $branch, $leaf): void {
            foreach ($records as $record) {
                $record->setAncestors($records);
                $record->setDescendants($records);
            }

            self::assertSame([], $root->getAncestors());
            self::assertSame([$root->id, $branch->id], array_keys($leaf->getAncestors()));
            self::assertSame([$branch->id, $leaf->id], array_keys($root->getDescendants()));
            self::assertSame([], $leaf->getDescendants());
        });

        self::assertSame(0, $queries);
    }

    public function testRefreshingTheAncestorsQueriesAgain(): void
    {
        $root = $this->createRecord('Root');
        $child = $this->createRecord('Child', $root);

        $child->setAncestors([]);
        self::assertSame([], $child->getAncestors());

        self::assertSame([$root->id], array_keys($child->getAncestors(true)));
    }

    public function testGetBranchCountCountsTheDescendants(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $this->createRecord('Leaf', $branch);

        $root->refresh();
        $branch->refresh();

        self::assertSame(2, $root->getBranchCount());
        self::assertSame(1, $branch->getBranchCount());

        // a record that is not in the tree yet has no `lft` / `rgt` at all
        self::assertSame(0, TestNestedTreeActiveRecord::create()->getBranchCount());
    }

    public function testDeleteNestedTreeItemsRemovesTheWholeBranch(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $this->createRecord('Leaf', $branch);
        $keep = $this->createRecord('Other root');

        $root->refresh();
        $root->deleteNestedTreeItems();

        self::assertSame(2, (int)TestNestedTreeActiveRecord::find()->count());
        self::assertNotNull(TestNestedTreeActiveRecord::findOne($root->id));
        self::assertNotNull(TestNestedTreeActiveRecord::findOne($keep->id));
    }

    public function testMovingABranchIntoAnotherOne(): void
    {
        $first = $this->createRecord('First');
        $second = $this->createRecord('Second');

        $branch = $this->createRecord('Branch', $first);
        $leaf = $this->createRecord('Leaf', $branch);

        $branch->populateParentRelation($second);

        self::assertTrue($branch->save());

        $first->refresh();
        $second->refresh();
        $leaf->refresh();
        $branch->refresh();

        self::assertSame(0, $first->getBranchCount());
        self::assertSame(2, $second->getBranchCount());
        self::assertSame(1, $branch->depth);
        self::assertSame(2, $leaf->depth);

        // the tree is still consistent, so a rebuild finds nothing to change
        self::assertSame(0, TestNestedTreeActiveRecord::rebuildNestedTree());
    }

    public function testRebuildNestedTreeRepairsTheWholeTree(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $leaf = $this->createRecord('Leaf', $branch);

        TestNestedTreeActiveRecord::updateAll(['lft' => 100, 'rgt' => 200, 'depth' => 9], ['id' => $leaf->id]);

        self::assertSame(1, TestNestedTreeActiveRecord::rebuildNestedTree());

        $leaf->refresh();

        self::assertSame(3, (int)$leaf->lft);
        self::assertSame(4, (int)$leaf->rgt);
        self::assertSame(2, (int)$leaf->depth);
    }

    public function testIndentNestedTreeMarksTheDepthOfEachRecord(): void
    {
        $root = $this->createRecord('Root');
        $branch = $this->createRecord('Branch', $root);
        $this->createRecord('Leaf', $branch);
        $this->createRecord('Sibling', $root);
        $this->createRecord('Other root');

        $records = TestNestedTreeActiveRecord::find()
            ->orderBy(['lft' => SORT_ASC])
            ->all();

        self::assertSame([
            'Root',
            '- Branch',
            '-- Leaf',
            '- Sibling',
            'Other root',
        ], array_values(TestNestedTreeActiveRecord::indentNestedTree($records, 'name')));
    }

    private function createRecord(string $name, ?TestNestedTreeActiveRecord $parent = null): TestNestedTreeActiveRecord
    {
        $record = TestNestedTreeActiveRecord::create();
        $record->name = $name;
        $record->populateParentRelation($parent);

        self::assertTrue($record->save());

        return $record;
    }
}

/**
 * @property int $id
 * @property string $name
 * @property int $position
 */
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
