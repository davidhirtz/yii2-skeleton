<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Traits;

use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Translation;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ConstraintFinderInterface;
use yii\db\IndexConstraint;
use yii\rbac\DbManager;
use yii\rbac\Permission;

trait MigrationTrait
{
    protected function getAuthManager(): DbManager
    {
        return Yii::$app->getAuthManager();
    }

    protected function addPermission(string $name, Message $description, string ...$parents): Permission
    {
        $auth = $this->getAuthManager();

        $permission = $auth->createPermission($name);
        $permission->description = $description->toJson();
        $auth->add($permission);

        foreach ($parents as $parent) {
            $auth->addChild($auth->getRole($parent) ?? $auth->getPermission($parent), $permission);
        }

        $auth->invalidateCache();

        return $permission;
    }

    /**
     * Grants `$new` to every parent and every assignee of any of the `$old` items, then deletes those — so an
     * assignee of a single old verb permission ends up with the whole model.
     *
     * @param list<string> $old
     */
    protected function replaceAuthItems(array $old, string $new): void
    {
        $auth = $this->getAuthManager();
        $db = $this->getDb();

        $old = array_values(array_diff($old, [$new]));

        if (!$old) {
            return;
        }

        $names = implode(', ', array_map($db->quoteValue(...), $old));
        $item = $db->quoteValue($new);

        $itemTable = $this->getQuotedTableName($auth->itemTable);
        $itemChildTable = $this->getQuotedTableName($auth->itemChildTable);
        $assignmentTable = $this->getQuotedTableName($auth->assignmentTable);

        $this->execute("
            INSERT IGNORE INTO $itemChildTable ([[parent]], [[child]])
            SELECT DISTINCT [[parent]], $item
            FROM $itemChildTable
            WHERE [[child]] IN ($names) AND [[parent]] NOT IN ($names)
        ");

        $this->execute("
            INSERT IGNORE INTO $assignmentTable ([[item_name]], [[user_id]], [[created_at]])
            SELECT DISTINCT $item, [[user_id]], " . time() . "
            FROM $assignmentTable
            WHERE [[item_name]] IN ($names)
        ");

        $this->execute("DELETE FROM $itemTable WHERE [[name]] IN ($names)");

        $auth->invalidateCache();
    }

    /**
     * The reverse of {@see MigrationTrait::replaceAuthItems()}. The old items share the new item's description and
     * parents: the keys that described them one by one are gone, and so is the hierarchy between them.
     *
     * @param list<string> $old
     */
    protected function restoreAuthItems(array $old, string $new, Message $description): void
    {
        $auth = $this->getAuthManager();
        $db = $this->getDb();

        $old = array_values(array_diff($old, [$new]));

        if (!$old) {
            return;
        }

        $item = $db->quoteValue($new);

        $itemTable = $this->getQuotedTableName($auth->itemTable);
        $itemChildTable = $this->getQuotedTableName($auth->itemChildTable);
        $assignmentTable = $this->getQuotedTableName($auth->assignmentTable);

        foreach ($old as $name) {
            $permission = $auth->createPermission($name);
            $permission->description = $description->toJson();
            $auth->add($permission);

            $name = $db->quoteValue($name);

            $this->execute("
                INSERT IGNORE INTO $itemChildTable ([[parent]], [[child]])
                SELECT DISTINCT [[parent]], $name
                FROM $itemChildTable
                WHERE [[child]] = $item
            ");

            $this->execute("
                INSERT IGNORE INTO $assignmentTable ([[item_name]], [[user_id]], [[created_at]])
                SELECT DISTINCT $name, [[user_id]], " . time() . "
                FROM $assignmentTable
                WHERE [[item_name]] = $item
            ");
        }

        $this->execute("DELETE FROM $itemTable WHERE [[name]] = $item");

        $auth->invalidateCache();
    }

    protected function getTableOptions(): ?string
    {
        $db = $this->getDb();

        if ($db->getDriverName() === 'mysql') {
            return $db->charset === 'utf8mb4'
                ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE InnoDB'
                : 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE InnoDB';
        }

        throw new InvalidConfigException();
    }

    protected function addCustomAttributesColumn(string $table, string $column = 'custom_attributes', ?string $after = null): void
    {
        $type = $this->json()->null();

        if ($after !== null) {
            $type->after($after);
        }

        $this->addColumn($table, $column, (string)$type);
    }

    protected function dropCustomAttributesColumn(string $table, string $column = 'custom_attributes'): void
    {
        $this->dropColumnIfExists($table, $column);
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->getDb()->getTableSchema($table)->getColumn($column)) {
            $this->dropColumn($table, $column);
        }
    }

    protected function dropIndexIfExists(string $name, string $table): void
    {
        try {
            $this->dropIndex($name, $table);
        } catch (Exception) {
            echo " skipped\n";
        }
    }

    protected function getForeignKeyName(string $tableName, string $column): string
    {
        $tableName = $this->getDb()->getSchema()->getRawTableName($tableName);
        return $tableName . '_' . $column;
    }

    /**
     * Only the configured languages and attributes are moved; any other `_xx` column is left alone.
     */
    protected function moveI18nColumnsToTranslations(ActiveRecord&TranslationInterface $model): void
    {
        $db = $this->getDb();
        $table = $model::tableName();

        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $class = $db->quoteValue($model->getTranslationModelClass());

        foreach ($model->getTranslatedAttributeNames() as $column => [$attribute, $language]) {
            if (!$this->hasColumn($table, $column)) {
                continue;
            }

            $this->execute("
                INSERT INTO $translations ([[model_class]], [[model_id]], [[language]], [[attribute]], [[value]])
                SELECT $class, [[id]], {$db->quoteValue($language)}, {$db->quoteValue($attribute)}, [[$column]]
                FROM $owner
                WHERE [[$column]] IS NOT NULL AND [[$column]] != ''
            ");

            $this->dropIndexesContainingColumn($table, $column);
            $this->dropColumn($table, $column);
        }
    }

    /**
     * MySQL silently removes a dropped column from a composite index, which would leave `(parent_id, slug_de)` as a
     * unique index on `parent_id`.
     */
    protected function dropIndexesContainingColumn(string $table, string $column): void
    {
        $schema = $this->getDb()->getSchema();

        if (!$schema instanceof ConstraintFinderInterface) {
            return;
        }

        /** @var IndexConstraint[] $indexes */
        $indexes = $schema->getTableIndexes($table, true);

        foreach ($indexes as $index) {
            if (!$index->isPrimary && in_array($column, (array)$index->columnNames, true)) {
                $this->dropIndex((string)$index->name, $table);
            }
        }
    }

    /**
     * Indexes are not restored; the migration that dropped one recreates it.
     */
    protected function restoreI18nColumnsFromTranslations(ActiveRecord&TranslationInterface $model): void
    {
        $db = $this->getDb();
        $table = $model::tableName();

        $this->addI18nColumns($table, $model->getTranslationAttributes());

        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $class = $db->quoteValue($model->getTranslationModelClass());

        foreach ($model->getTranslatedAttributeNames() as $column => [$attribute, $language]) {
            if (!$this->hasColumn($table, $column)) {
                continue;
            }

            $this->execute("
                UPDATE $owner AS [[owner]]
                INNER JOIN $translations AS [[translation]]
                    ON [[translation]].[[model_class]] = $class
                    AND [[translation]].[[model_id]] = [[owner]].[[id]]
                    AND [[translation]].[[language]] = {$db->quoteValue($language)}
                    AND [[translation]].[[attribute]] = {$db->quoteValue($attribute)}
                SET [[owner]].[[$column]] = [[translation]].[[value]]
            ");
        }

        $this->execute("DELETE FROM $translations WHERE [[model_class]] = $class");
    }

    /**
     * @param list<string> $attributes
     */
    private function addI18nColumns(string $table, array $attributes): void
    {
        $schema = $this->getDb()->getSchema();
        $tableSchema = $schema->getTableSchema($table, true);
        $i18n = Yii::$app->getI18n();

        foreach ($attributes as $attribute) {
            $column = $tableSchema->getColumn($attribute);

            if (!$column) {
                continue;
            }

            $previous = $attribute;
            $type = $schema->createColumnSchemaBuilder($column->type, $column->size)
                ->defaultValue($column->defaultValue);

            foreach ($i18n->getLanguages() as $language) {
                if ($language === Yii::$app->sourceLanguage) {
                    continue;
                }

                $type->append("AFTER [[$previous]]");
                $previous = $i18n->getAttributeName($attribute, $language);

                if (!$tableSchema->getColumn($previous)) {
                    $this->addColumn($table, $previous, (string)$type);
                }
            }
        }
    }

    protected function getQuotedTableName(string $tableName): string
    {
        $db = $this->getDb();
        return $db->quoteTableName($db->getSchema()->getRawTableName($tableName));
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return $this->getDb()->getSchema()->getTableSchema($table, true)->getColumn($column) !== null;
    }
}
