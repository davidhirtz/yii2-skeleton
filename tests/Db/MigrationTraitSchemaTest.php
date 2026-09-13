<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Migration;

/**
 * Every helper here runs DDL, which commits the surrounding transaction in MySQL, so the scratch table is created
 * and dropped outside it.
 */
class MigrationTraitSchemaTest extends TestCase
{
    private const string TABLE = 'test_migration_trait';

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->createTable(self::TABLE, [
                'id' => 'pk',
                'parent_id' => 'integer unsigned NULL DEFAULT NULL',
                'slug' => 'string(100) NOT NULL',
                'name' => 'string(100) NULL',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(self::TABLE)
            ->execute();
    }

    public function testHasColumn(): void
    {
        $migration = $this->createMigration();

        self::assertTrue($migration->hasColumn(self::TABLE, 'slug'));
        self::assertFalse($migration->hasColumn(self::TABLE, 'nope'));
    }

    public function testDropColumnIfExistsSkipsAColumnThatIsAlreadyGone(): void
    {
        $migration = $this->createMigration();

        $migration->dropColumnIfExists(self::TABLE, 'name');
        self::assertFalse($migration->hasColumn(self::TABLE, 'name'));

        $migration->dropColumnIfExists(self::TABLE, 'name');
        self::assertFalse($migration->hasColumn(self::TABLE, 'name'));
    }

    public function testDropIndexIfExistsSkipsAnIndexThatIsAlreadyGone(): void
    {
        $migration = $this->createMigration();
        $migration->createIndex('test_slug', self::TABLE, 'slug');

        $migration->dropIndexIfExists('test_slug', self::TABLE);
        self::assertSame([], $this->getIndexNames());

        $migration->dropIndexIfExists('test_slug', self::TABLE);
        self::assertSame([], $this->getIndexNames());
    }

    public function testAddCustomAttributesColumn(): void
    {
        $migration = $this->createMigration();
        $migration->addCustomAttributesColumn(self::TABLE, after: 'slug');

        $column = Yii::$app->getDb()
            ->getSchema()
            ->getTableSchema(self::TABLE, true)
            ->getColumn('custom_attributes');

        self::assertNotNull($column);
        self::assertTrue($column->allowNull);

        $migration->dropCustomAttributesColumn(self::TABLE);
        self::assertFalse($migration->hasColumn(self::TABLE, 'custom_attributes'));
    }

    /**
     * MySQL and MariaDB drop the column out of a composite index instead of dropping the index, which would leave a
     * unique index on `(parent_id, slug)` as a unique index on `parent_id` alone.
     */
    public function testDropIndexesContainingColumnDropsTheWholeIndex(): void
    {
        $migration = $this->createMigration();
        $migration->createIndex('test_parent_slug', self::TABLE, ['parent_id', 'slug'], true);
        $migration->createIndex('test_name', self::TABLE, 'name');

        self::assertEqualsCanonicalizing(['test_parent_slug', 'test_name'], $this->getIndexNames());

        $migration->dropIndexesContainingColumn(self::TABLE, 'slug');

        self::assertSame(['test_name'], $this->getIndexNames());
    }

    public function testDropIndexesContainingColumnKeepsThePrimaryKey(): void
    {
        $migration = $this->createMigration();
        $migration->dropIndexesContainingColumn(self::TABLE, 'id');

        self::assertNotNull(Yii::$app->getDb()->getSchema()->getTablePrimaryKey(self::TABLE, true));
    }

    public function testGetForeignKeyName(): void
    {
        $migration = $this->createMigration();

        self::assertSame('user_parent_id', $migration->getForeignKeyName('{{%user}}', 'parent_id'));
        self::assertSame('user_parent_id', $migration->getForeignKeyName('user', 'parent_id'));
    }

    public function testGetQuotedTableNameResolvesThePrefix(): void
    {
        $migration = $this->createMigration();
        self::assertSame('`user`', $migration->getQuotedTableName('{{%user}}'));
    }

    public function testGetTableOptionsFollowsTheConnectionCharset(): void
    {
        $migration = $this->createMigration();
        $db = Yii::$app->getDb();

        $db->charset = 'utf8mb4';
        self::assertStringContainsString('utf8mb4_unicode_ci', $migration->getTableOptions());

        $db->charset = 'utf8';
        self::assertStringContainsString('utf8_unicode_ci', $migration->getTableOptions());
    }

    /**
     * @return list<string>
     */
    private function getIndexNames(): array
    {
        $indexes = Yii::$app->getDb()
            ->getSchema()
            ->getTableIndexes(self::TABLE, true);

        $names = [];

        foreach ($indexes as $index) {
            if (!$index->isPrimary) {
                $names[] = (string)$index->name;
            }
        }

        return $names;
    }

    private function createMigration(): TestSchemaMigration
    {
        return new TestSchemaMigration(['compact' => true]);
    }
}

class TestSchemaMigration extends Migration
{
    use MigrationTrait {
        addCustomAttributesColumn as public;
        dropColumnIfExists as public;
        dropCustomAttributesColumn as public;
        dropIndexesContainingColumn as public;
        dropIndexIfExists as public;
        getForeignKeyName as public;
        getQuotedTableName as public;
        getTableOptions as public;
        hasColumn as public;
    }
}
