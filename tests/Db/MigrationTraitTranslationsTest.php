<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Migration;
use yii\db\Query;

/**
 * The round trip an upgrade and its rollback run: the `_de` columns move into the `translation` table and back.
 * Everything here is DDL, which commits the surrounding transaction, so the rows are cleaned up by hand.
 */
class MigrationTraitTranslationsTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    #[Override]
    protected function tearDown(): void
    {
        Translation::deleteAll(['model_class' => TranslatedMigrationRecord::class]);
        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->createTable(TranslatedMigrationRecord::tableName(), [
                'id' => 'pk',
                'parent_id' => 'integer unsigned NULL DEFAULT NULL',
                'name' => 'string(100) NULL',
                'name_de' => 'string(100) NULL',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->dropTable(TranslatedMigrationRecord::tableName())
            ->execute();
    }

    public function testTheTranslatedColumnsMoveIntoTheTranslationTable(): void
    {
        $id = $this->insertRow('Name', 'Name DE');
        $empty = $this->insertRow('Only source', '');

        $migration = $this->createMigration();
        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        self::assertFalse($migration->hasColumn(TranslatedMigrationRecord::tableName(), 'name_de'));

        $translations = Translation::find()
            ->where(['model_class' => TranslatedMigrationRecord::class])
            ->all();

        self::assertCount(1, $translations);

        $translation = reset($translations);
        self::assertNotFalse($translation);

        self::assertSame((string)$id, (string)$translation->model_id);
        self::assertSame('de', $translation->language);
        self::assertSame('name', $translation->attribute);
        self::assertSame('Name DE', $translation->value);

        // an empty translation is no translation, so no row is written for it
        self::assertSame(0, (int)Translation::find()->where(['model_id' => $empty])->count());
    }

    /**
     * MySQL drops a column out of a composite index rather than dropping the index, which would silently turn a
     * unique index on `(parent_id, name_de)` into one on `parent_id` alone.
     */
    public function testAnIndexContainingATranslatedColumnIsDropped(): void
    {
        $migration = $this->createMigration();
        $migration->createIndex('test_parent_name_de', TranslatedMigrationRecord::tableName(), ['parent_id', 'name_de'], true);

        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        $indexes = Yii::$app->getDb()
            ->getSchema()
            ->getTableIndexes(TranslatedMigrationRecord::tableName(), true);

        foreach ($indexes as $index) {
            self::assertTrue($index->isPrimary, "Index $index->name survived the column it contained.");
        }

        $this->insertRow('First', null, 1);
        $this->insertRow('Second', null, 1);

        self::assertSame(2, (int)TranslatedMigrationRecord::find()->where(['parent_id' => 1])->count());
    }

    public function testTheColumnsAreRebuiltFromTheTranslationsOnTheWayDown(): void
    {
        $id = $this->insertRow('Name', 'Name DE');

        $migration = $this->createMigration();
        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);
        $migration->restoreI18nColumnsFromTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        self::assertTrue($migration->hasColumn(TranslatedMigrationRecord::tableName(), 'name_de'));

        $row = (new Query())
            ->from(TranslatedMigrationRecord::tableName())
            ->where(['id' => $id])
            ->one();

        self::assertIsArray($row);

        self::assertSame('Name', $row['name']);
        self::assertSame('Name DE', $row['name_de']);

        // the rows are the columns' own again, so nothing is left behind in the polymorphic table
        self::assertSame(0, (int)Translation::find()->where(['model_class' => TranslatedMigrationRecord::class])->count());
    }

    /**
     * The columns are discovered from the table rather than from the model, and a `_xx` column whose source
     * column is gone is not one of them — the pair is what makes it a translation.
     */
    public function testAColumnWithoutItsSourceColumnIsLeftAlone(): void
    {
        $migration = $this->createMigration();
        $migration->addColumn(TranslatedMigrationRecord::tableName(), 'title_de', (string)$migration->string(100)->null());

        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        self::assertTrue($migration->hasColumn(TranslatedMigrationRecord::tableName(), 'title_de'));
        self::assertFalse($migration->hasColumn(TranslatedMigrationRecord::tableName(), 'name_de'));
    }

    /**
     * The rows say which columns to rebuild, so a translation the installation no longer configures a language
     * for is restored rather than deleted unread.
     */
    public function testATranslationInAnUnconfiguredLanguageIsRestored(): void
    {
        $id = $this->insertRow('Name', 'Name DE');

        $migration = $this->createMigration();
        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        $translation = Translation::create();
        $translation->model_class = TranslatedMigrationRecord::class;
        $translation->model_id = $id;
        $translation->language = 'fr';
        $translation->attribute = 'name';
        $translation->value = 'Name FR';

        self::assertTrue($translation->save());

        $migration->restoreI18nColumnsFromTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        $row = (new Query())
            ->from(TranslatedMigrationRecord::tableName())
            ->where(['id' => $id])
            ->one();

        self::assertIsArray($row);

        self::assertSame('Name DE', $row['name_de']);
        self::assertSame('Name FR', $row['name_fr']);
    }

    public function testAColumnThatIsAlreadyGoneIsSkipped(): void
    {
        $migration = $this->createMigration();
        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        // a second run finds nothing to move and must not fail
        $migration->moveI18nColumnsToTranslations(TranslatedMigrationRecord::tableName(), TranslatedMigrationRecord::class);

        self::assertFalse($migration->hasColumn(TranslatedMigrationRecord::tableName(), 'name_de'));
    }

    private function insertRow(?string $name, ?string $nameDe, ?int $parentId = null): int
    {
        $columns = ['name' => $name, 'parent_id' => $parentId];

        if ($this->createMigration()->hasColumn(TranslatedMigrationRecord::tableName(), 'name_de')) {
            $columns['name_de'] = $nameDe;
        }

        $db = Yii::$app->getDb();

        $db->createCommand()
            ->insert(TranslatedMigrationRecord::tableName(), $columns)
            ->execute();

        return (int)$db->getLastInsertID();
    }

    private function createMigration(): TestTranslationsMigration
    {
        return new TestTranslationsMigration(['compact' => true]);
    }
}

class TestTranslationsMigration extends Migration
{
    use MigrationTrait {
        hasColumn as public;
        moveI18nColumnsToTranslations as public;
        restoreI18nColumnsFromTranslations as public;
    }
}

class TranslatedMigrationRecord extends ActiveRecord implements TranslationInterface
{
    use I18nAttributesTrait;
    use TranslationTrait;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    /**
     * @return I18nActiveQuery<static>
     */
    #[Override]
    public static function find(): I18nActiveQuery
    {
        return Yii::createObject(I18nActiveQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return 'translated_migration_test';
    }
}
