<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\db\Migration;
use yii\db\Query;

/**
 * The round trip an upgrade and its rollback run: a column and its translations move into the JSON column and back.
 * Everything here is DDL, which commits the surrounding transaction, so the rows are cleaned up by hand.
 */
class MigrationTraitCustomAttributesTest extends TestCase
{
    private const string TABLE = 'custom_attributes_migration_test';
    private const string MODEL_CLASS = 'Hirtz\Skeleton\Tests\Db\CustomAttributesMigrationRecord';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    #[Override]
    protected function tearDown(): void
    {
        Translation::deleteAll(['model_class' => self::MODEL_CLASS]);
        parent::tearDown();
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()
            ->createCommand()
            ->createTable(self::TABLE, [
                'id' => 'pk',
                'name' => 'string(100) NULL',
                'content' => 'text NULL',
                'custom_attributes' => 'json NULL',
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

    public function testTheColumnsMoveIntoTheJsonColumn(): void
    {
        $id = $this->insertRow(['name' => 'Name', 'content' => '<p>Über</p>']);
        $empty = $this->insertRow(['name' => '', 'content' => null]);

        $migration = $this->createMigration();
        $migration->moveColumnsToCustomAttributes(self::TABLE, ['name', 'content'], self::MODEL_CLASS);

        self::assertFalse($migration->hasColumn(self::TABLE, 'name'));
        self::assertFalse($migration->hasColumn(self::TABLE, 'content'));

        self::assertSame(['name' => 'Name', 'content' => '<p>Über</p>'], $this->getCustomAttributes($id));

        // an empty column is no value, so it is not written at all
        self::assertSame([], $this->getCustomAttributes($empty));
    }

    public function testATranslationMovesIntoTheJsonColumnUnderItsSuffixedName(): void
    {
        $id = $this->insertRow(['name' => 'Name']);
        $this->insertTranslation($id, 'de', 'name', 'Name DE');
        $this->insertTranslation($id, 'de', 'content', '');

        $this->createMigration()->moveColumnsToCustomAttributes(self::TABLE, ['name', 'content'], self::MODEL_CLASS);

        self::assertSame(['name' => 'Name', 'name_de' => 'Name DE'], $this->getCustomAttributes($id));
        self::assertSame(0, (int)Translation::find()->where(['model_class' => self::MODEL_CLASS])->count());
    }

    /**
     * A project whose configuration stopped naming the attribute never had its `_xx` columns moved to the
     * translation table, so both shapes have to be read.
     */
    public function testALeftOverTranslatedColumnIsMovedToo(): void
    {
        $migration = $this->createMigration();
        $migration->addColumn(self::TABLE, 'name_de', 'string(100) NULL');
        $migration->createIndex('name_de', self::TABLE, ['name_de']);

        $id = $this->insertRow(['name' => 'Name', 'name_de' => 'Name DE']);

        $migration->moveColumnsToCustomAttributes(self::TABLE, ['name'], self::MODEL_CLASS);

        self::assertFalse($migration->hasColumn(self::TABLE, 'name_de'));
        self::assertSame(['name' => 'Name', 'name_de' => 'Name DE'], $this->getCustomAttributes($id));
    }

    public function testTheColumnsAreRebuiltOnTheWayDown(): void
    {
        $id = $this->insertRow(['name' => 'Name', 'content' => '<p>Text</p>']);
        $this->insertTranslation($id, 'de', 'name', 'Name DE');

        $migration = $this->createMigration();
        $migration->moveColumnsToCustomAttributes(self::TABLE, ['name', 'content'], self::MODEL_CLASS);

        $migration->restoreColumnsFromCustomAttributes(self::TABLE, [
            'name' => 'string(100) NULL',
            'content' => 'text NULL',
        ], self::MODEL_CLASS);

        $row = (new Query())->from(self::TABLE)->where(['id' => $id])->one();

        self::assertIsArray($row);

        self::assertSame('Name', $row['name']);
        self::assertSame('<p>Text</p>', $row['content']);

        // the JSON column keeps nothing of what went back into a column or a translation row
        self::assertSame([], $this->getCustomAttributes($id));

        $translation = Translation::find()->where(['model_class' => self::MODEL_CLASS])->one();

        self::assertNotNull($translation);
        self::assertSame('de', $translation->language);
        self::assertSame('name', $translation->attribute);
        self::assertSame('Name DE', $translation->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCustomAttributes(int $id): array
    {
        $value = (new Query())
            ->select('custom_attributes')
            ->from(self::TABLE)
            ->where(['id' => $id])
            ->scalar();

        return $value === null ? [] : (array)json_decode((string)$value, true);
    }

    /**
     * @param array<string, string|null> $columns
     */
    private function insertRow(array $columns): int
    {
        $db = Yii::$app->getDb();
        $db->createCommand()->insert(self::TABLE, $columns)->execute();

        return (int)$db->getLastInsertID();
    }

    private function insertTranslation(int $id, string $language, string $attribute, string $value): void
    {
        Yii::$app->getDb()->createCommand()->insert(Translation::tableName(), [
            'model_class' => self::MODEL_CLASS,
            'model_id' => $id,
            'language' => $language,
            'attribute' => $attribute,
            'value' => $value,
        ])->execute();
    }

    private function createMigration(): TestCustomAttributesMigration
    {
        return new TestCustomAttributesMigration(['compact' => true]);
    }
}

class TestCustomAttributesMigration extends Migration
{
    use MigrationTrait {
        hasColumn as public;
        moveColumnsToCustomAttributes as public;
        restoreColumnsFromCustomAttributes as public;
    }
}
