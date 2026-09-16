<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Traits;

use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Translation;
use RuntimeException;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ConstraintFinderInterface;
use yii\db\IndexConstraint;
use yii\db\Query;
use yii\db\TableSchema;
use yii\rbac\DbManager;
use yii\rbac\Item;
use yii\rbac\Permission;

trait MigrationTrait
{
    protected function getAuthManager(): DbManager
    {
        return Yii::$app->getAuthManager();
    }

    /**
     * A name no item carries names itself, rather than reaching `addChild()` as `null`.
     */
    protected function getAuthItem(string $name): Item
    {
        $auth = $this->getAuthManager();

        return $auth->getRole($name)
            ?? $auth->getPermission($name)
            ?? throw new RuntimeException("Auth item \"$name\" does not exist.");
    }

    protected function addPermission(string $name, Message $description, string ...$parents): Permission
    {
        $auth = $this->getAuthManager();

        $permission = $auth->createPermission($name);
        $permission->description = $description->toJson();
        $auth->add($permission);

        foreach ($parents as $parent) {
            $auth->addChild($this->getAuthItem($parent), $permission);
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

    /**
     * Cosmetic: MySQL can only reorder a column by rewriting its definition, and the JSON column has the same one
     * everywhere, so it is restated rather than read back from the schema.
     */
    protected function moveCustomAttributesColumn(string $table, string $after, string $column = 'custom_attributes'): void
    {
        $this->alterColumn($table, $column, (string)$this->json()->null()->after($after));
    }

    /**
     * The way back, since there is no last position to name — only a last column.
     */
    protected function moveCustomAttributesColumnToEnd(string $table, string $column = 'custom_attributes'): void
    {
        $columns = $this->getTableSchema($table)->getColumnNames();
        $columns = array_values(array_diff($columns, [$column]));

        $this->moveCustomAttributesColumn($table, (string)end($columns), $column);
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->getTableSchema($table)->getColumn($column)) {
            $this->dropColumn($table, $column);
        }
    }

    protected function dropIndexIfExists(string $name, string $table): void
    {
        if ($this->hasIndex($table, $name)) {
            $this->dropIndex($name, $table);
        }
    }

    protected function getForeignKeyName(string $tableName, string $column): string
    {
        $tableName = $this->getDb()->getSchema()->getRawTableName($tableName);
        return $tableName . '_' . $column;
    }

    /**
     * Moves a column and any `<column>_<language>` beside it into the JSON column under the column's own name, then
     * the {@see Translation} rows of the same attributes under their suffixed name, and drops the columns. Both
     * shapes are read because a project whose configuration no longer names the attribute never had its columns
     * moved to the translation table in the first place.
     *
     * @param list<string> $attributes
     * @param string $modelClass the `model_class` of the translation rows, which a migration spells out: the class
     * behind it may be gone by the time the migration runs
     */
    protected function moveColumnsToCustomAttributes(
        string $table,
        array $attributes,
        string $modelClass,
        string $column = 'custom_attributes'
    ): void {
        $columns = $this->getCustomAttributeColumns($table, $attributes);

        if ($columns) {
            $db = $this->getDb();
            $owner = $this->getQuotedTableName($table);
            $target = $db->quoteColumnName($column);

            $values = implode(', ', array_map(
                fn (string $name): string => $db->quoteValue($name) . ", NULLIF([[$name]], '')",
                $columns
            ));

            $this->execute("
                UPDATE $owner SET $target = JSON_MERGE_PATCH(COALESCE($target, '{}'), JSON_OBJECT($values))
            ");
        }

        $this->assertCustomAttributeColumns($table, $columns, $column);
        $this->moveTranslationsToCustomAttributes($table, $attributes, $modelClass, $column);

        foreach ($columns as $name) {
            $this->dropIndexesContainingColumn($table, $name);
            $this->dropColumn($table, $name);
        }
    }

    /**
     * @param list<string> $columns
     */
    private function assertCustomAttributeColumns(string $table, array $columns, string $column): void
    {
        $db = $this->getDb();
        $owner = $this->getQuotedTableName($table);
        $target = $db->quoteColumnName($column);

        foreach ($columns as $name) {
            $path = $db->quoteValue('$."' . $name . '"');

            $count = (int)$db->createCommand("
                SELECT COUNT(*) FROM $owner
                WHERE NOT (JSON_UNQUOTE(JSON_EXTRACT($target, $path)) <=> NULLIF([[$name]], ''))
            ")->queryScalar();

            if ($count !== 0) {
                throw new RuntimeException("Column \"$name\" of $table did not reach $column in $count rows.");
            }
        }

        if (!$this->compact) {
            echo '    > moved ' . count($columns) . " columns of $table\n";
        }
    }

    /**
     * @param list<string> $attributes
     */
    protected function moveTranslationsToCustomAttributes(
        string $table,
        array $attributes,
        string $modelClass,
        string $column = 'custom_attributes'
    ): void {
        $db = $this->getDb();
        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $target = $db->quoteColumnName($column);
        $class = $db->quoteValue($modelClass);
        $i18n = Yii::$app->getI18n();

        foreach ($attributes as $attribute) {
            foreach ($i18n->getLanguages() as $language) {
                $name = $i18n->getAttributeName($attribute, $language);
                $path = $db->quoteValue('$."' . $name . '"');

                $this->execute("
                    UPDATE $owner [[o]] JOIN $translations [[t]]
                        ON [[t]].[[model_class]] = $class AND [[t]].[[model_id]] = [[o]].[[id]]
                        AND [[t]].[[attribute]] = {$db->quoteValue($attribute)}
                        AND [[t]].[[language]] = {$db->quoteValue($language)}
                    SET [[o]].$target = JSON_SET(COALESCE([[o]].$target, '{}'), $path, [[t]].[[value]])
                    WHERE [[t]].[[value]] IS NOT NULL AND [[t]].[[value]] != ''
                ");
            }

            $this->delete(Translation::tableName(), [
                'model_class' => $modelClass,
                'attribute' => $attribute,
            ]);
        }
    }

    /**
     * The reverse: the source language goes back into its column, every other language back into a
     * {@see Translation} row, and the keys are removed from the JSON.
     *
     * @param array<string, string> $columns the column definition per attribute
     */
    protected function restoreColumnsFromCustomAttributes(
        string $table,
        array $columns,
        string $modelClass,
        string $column = 'custom_attributes'
    ): void {
        $db = $this->getDb();
        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $target = $db->quoteColumnName($column);
        $class = $db->quoteValue($modelClass);
        $i18n = Yii::$app->getI18n();
        $paths = [];

        foreach ($columns as $attribute => $type) {
            if (!$this->hasColumn($table, $attribute)) {
                $this->addColumn($table, $attribute, $type);
            }

            foreach ($i18n->getLanguages() as $language) {
                $name = $i18n->getAttributeName($attribute, $language);
                $path = $db->quoteValue('$."' . $name . '"');
                $paths[] = $path;

                if ($name === $attribute) {
                    $this->execute("
                        UPDATE $owner SET [[$attribute]] = JSON_UNQUOTE(JSON_EXTRACT($target, $path))
                        WHERE JSON_EXTRACT($target, $path) IS NOT NULL
                    ");

                    continue;
                }

                $this->execute("
                    INSERT INTO $translations ([[model_class]], [[model_id]], [[language]], [[attribute]], [[value]])
                    SELECT $class, [[id]], {$db->quoteValue($language)}, {$db->quoteValue($attribute)},
                        JSON_UNQUOTE(JSON_EXTRACT($target, $path))
                    FROM $owner WHERE JSON_EXTRACT($target, $path) IS NOT NULL
                ");
            }
        }

        $this->execute("UPDATE $owner SET $target = JSON_REMOVE($target, " . implode(', ', $paths) . ')');
    }

    /**
     * @param list<string> $attributes
     * @return list<string> the attribute columns and their `_xx` variants, in the order they are written
     */
    private function getCustomAttributeColumns(string $table, array $attributes): array
    {
        $columns = [];

        foreach ($this->getTableSchema($table)->getColumnNames() as $name) {
            foreach ($attributes as $attribute) {
                if ($name === $attribute || str_starts_with($name, $attribute . '_')) {
                    $columns[] = $name;
                }
            }
        }

        return $columns;
    }

    /**
     * The columns are read off the table, never off the model: what a v2 database translated is what it has
     * columns for, and the v3 model that would answer today declares a different set — one whose resolution can
     * need columns this point in history does not have yet.
     *
     * @param class-string $modelClass the `model_class` the records are filed under
     */
    protected function moveI18nColumnsToTranslations(string $table, string $modelClass): void
    {
        $db = $this->getDb();

        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $model = $this->getTranslationModelColumn();
        $class = $db->quoteValue($modelClass);

        foreach ($this->getI18nColumns($table) as $column => [$attribute, $language]) {
            $this->execute("
                INSERT INTO $translations ([[$model]], [[model_id]], [[language]], [[attribute]], [[value]])
                SELECT $class, [[id]], {$db->quoteValue($language)}, {$db->quoteValue($attribute)}, [[$column]]
                FROM $owner
                WHERE [[$column]] IS NOT NULL AND [[$column]] != ''
            ");

            $this->dropIndexesContainingColumn($table, $column);
            $this->dropColumn($table, $column);
        }
    }

    /**
     * @return array<string, array{string, string}> the translated columns of the table, each mapped to its
     * attribute and language. A `<attribute>_<language>` counts only while the source column is there too.
     */
    protected function getI18nColumns(string $table): array
    {
        $i18n = Yii::$app->getI18n();
        $names = $this->getTableSchema($table)->getColumnNames();
        $columns = [];

        foreach ($names as $attribute) {
            foreach ($i18n->getLanguages() as $language) {
                $column = $i18n->getAttributeName($attribute, $language);

                if ($column !== $attribute && in_array($column, $names, true)) {
                    $columns[$column] = [$attribute, $language];
                }
            }
        }

        return $columns;
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
     * The rows themselves say which columns to rebuild, so a translation in a language the installation has since
     * dropped is restored as well. Indexes are not; the migration that dropped one recreates it.
     *
     * @param class-string $modelClass the `model_class` the records are filed under
     */
    protected function restoreI18nColumnsFromTranslations(string $table, string $modelClass): void
    {
        $db = $this->getDb();

        $owner = $this->getQuotedTableName($table);
        $translations = $this->getQuotedTableName(Translation::tableName());
        $model = $this->getTranslationModelColumn();
        $class = $db->quoteValue($modelClass);

        foreach ($this->getTranslatedAttributes($modelClass) as [$attribute, $language]) {
            $column = $this->addI18nColumn($table, $attribute, $language);

            if (!$column) {
                continue;
            }

            $this->execute("
                UPDATE $owner AS [[owner]]
                INNER JOIN $translations AS [[translation]]
                    ON [[translation]].[[$model]] = $class
                    AND [[translation]].[[model_id]] = [[owner]].[[id]]
                    AND [[translation]].[[language]] = {$db->quoteValue($language)}
                    AND [[translation]].[[attribute]] = {$db->quoteValue($attribute)}
                SET [[owner]].[[$column]] = [[translation]].[[value]]
            ");
        }

        $this->execute("DELETE FROM $translations WHERE [[$model]] = $class");
    }

    /**
     * The column is `model` until {@see \Hirtz\Skeleton\Migrations\M260912090000ModelClass} renames it, and
     * that runs well after the migrations that fill the table — so an upgrade meets both names.
     */
    private function getTranslationModelColumn(): string
    {
        return $this->hasColumn(Translation::tableName(), 'model_class') ? 'model_class' : 'model';
    }

    /**
     * @param class-string $modelClass
     * @return list<array{string, string}> the attribute and language of every translation the model has
     */
    private function getTranslatedAttributes(string $modelClass): array
    {
        $rows = (new Query())
            ->select(['attribute', 'language'])
            ->distinct()
            ->from(Translation::tableName())
            ->where([$this->getTranslationModelColumn() => $modelClass])
            ->orderBy(['attribute' => SORT_ASC, 'language' => SORT_ASC])
            ->all($this->getDb());

        return array_values(array_map(static fn (array $row): array => [(string)$row['attribute'], (string)$row['language']], $rows));
    }

    /**
     * @return string|null the column, `null` while the source column it is typed and placed after is missing
     */
    private function addI18nColumn(string $table, string $attribute, string $language): ?string
    {
        $schema = $this->getDb()->getSchema();
        $tableSchema = $this->getTableSchema($table);
        $source = $tableSchema->getColumn($attribute);
        $column = Yii::$app->getI18n()->getAttributeName($attribute, $language);

        if (!$source || $column === $attribute) {
            return null;
        }

        if (!$tableSchema->getColumn($column)) {
            $type = $schema->createColumnSchemaBuilder($source->type, $source->size)
                ->defaultValue($source->defaultValue)
                ->append("AFTER [[{$this->getPreviousI18nColumn($tableSchema->getColumnNames(), $attribute)}]]");

            $this->addColumn($table, $column, (string)$type);
        }

        return $column;
    }

    /**
     * @param array<string> $names
     * @return string the attribute's last language column, so a new one lands beside its siblings
     */
    private function getPreviousI18nColumn(array $names, string $attribute): string
    {
        $i18n = Yii::$app->getI18n();
        $previous = $attribute;

        foreach ($i18n->getLanguages() as $language) {
            $name = $i18n->getAttributeName($attribute, $language);

            if ($name !== $attribute && in_array($name, $names, true)) {
                $previous = $name;
            }
        }

        return $previous;
    }

    protected function getQuotedTableName(string $tableName): string
    {
        $db = $this->getDb();
        return $db->quoteTableName($db->getSchema()->getRawTableName($tableName));
    }

    /**
     * The schema is read fresh, since a migration is part-way through changing it, and a missing table names
     * itself rather than leaving Yii to report a call on `null`.
     */
    protected function getTableSchema(string $table): TableSchema
    {
        return $this->getDb()->getSchema()->getTableSchema($table, true)
            ?? throw new RuntimeException("Table \"$table\" does not exist.");
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return $this->getTableSchema($table)->getColumn($column) !== null;
    }

    protected function hasIndex(string $table, string $name): bool
    {
        $schema = $this->getDb()->getSchema();

        if (!$schema instanceof ConstraintFinderInterface) {
            return false;
        }

        /** @var IndexConstraint[] $indexes */
        $indexes = $schema->getTableIndexes($table, true);

        foreach ($indexes as $index) {
            if ($index->name === $name) {
                return true;
            }
        }

        return false;
    }
}
