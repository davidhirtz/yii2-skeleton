<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db\Traits;

use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Translation;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ConstraintFinderInterface;
use yii\db\IndexConstraint;
use yii\rbac\DbManager;

trait MigrationTrait
{
    protected function getAuthManager(): DbManager
    {
        return Yii::$app->getAuthManager();
    }

    protected function getTableOptions(): ?string
    {
        $db = Yii::$app->getDb();

        if ($db->getDriverName() === 'mysql') {
            return $db->charset === 'utf8mb4'
                ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE InnoDB'
                : 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE InnoDB';
        }

        throw new InvalidConfigException();
    }

    protected function addI18nColumns(string $table, array $attributes, bool $allowNull = false, ?array $except = null): void
    {
        if ($attributes) {
            $schema = Yii::$app->getDb()->getSchema();
            $tableSchema = $schema->getTableSchema($table);
            $i18n = Yii::$app->getI18n();
            $languages = $i18n->getLanguages();

            $except ??= [Yii::$app->sourceLanguage];

            foreach ($attributes as $attribute) {
                $column = $tableSchema->getColumn($attribute);

                if ($column) {
                    $prevAttribute = $attribute;
                    $type = $schema->createColumnSchemaBuilder($column->type, $column->size)->defaultValue($column->defaultValue);

                    if ($column->unsigned) {
                        $type->unsigned();
                    }

                    if ($allowNull && !$column->allowNull) {
                        $type->notNull();
                    }

                    foreach ($languages as $language) {
                        if (!in_array($language, $except, true)) {
                            $type->append("AFTER [[$prevAttribute]]");
                            $prevAttribute = $i18n->getAttributeName($attribute, $language);

                            if (!$tableSchema->getColumn($prevAttribute)) {
                                $this->addColumn($table, $prevAttribute, (string)$type);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->getDb()->getTableSchema($table)->getColumn($column)) {
            $this->dropColumn($table, $column);
        }
    }

    protected function dropI18nColumns(string $table, array $attributes, ?array $except = []): void
    {
        if ($attributes) {
            $i18n = Yii::$app->getI18n();
            $languages = $i18n->getLanguages();
            $tableSchema = Yii::$app->getDb()->getSchema()->getTableSchema($table);

            foreach ($attributes as $attribute) {
                foreach ($languages as $language) {
                    $column = $i18n->getAttributeName($attribute, $language);

                    if (!in_array($language, $except, true) && $tableSchema->getColumn($column)) {
                        $this->dropColumn($table, $column);
                    }
                }
            }
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
     * Moves every translated attribute of the given model from its `_xx` column into a {@see Translation} record and
     * drops the column. Columns of a language the application no longer configures are left alone, as is any
     * attribute a project removed from `i18nAttributes`.
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
                INSERT INTO $translations ([[model]], [[model_id]], [[language]], [[attribute]], [[value]])
                SELECT $class, [[id]], {$db->quoteValue($language)}, {$db->quoteValue($attribute)}, [[$column]]
                FROM $owner
                WHERE [[$column]] IS NOT NULL AND [[$column]] != ''
            ");

            $this->dropIndexesContainingColumn($table, $column);
            $this->dropColumn($table, $column);
        }
    }

    /**
     * MySQL and MariaDB silently remove a dropped column from a composite index rather than dropping the index, which
     * turns a unique index on `(parent_id, slug_de)` into a unique index on `parent_id` alone. So the indexes have to
     * go before the column does.
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
     * Recreates the `_xx` columns from their source column definition and fills them from the {@see Translation}
     * records, which are then removed. Indexes are not restored: a migration that dropped one recreates it itself.
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
                    ON [[translation]].[[model]] = $class
                    AND [[translation]].[[model_id]] = [[owner]].[[id]]
                    AND [[translation]].[[language]] = {$db->quoteValue($language)}
                    AND [[translation]].[[attribute]] = {$db->quoteValue($attribute)}
                SET [[owner]].[[$column]] = [[translation]].[[value]]
            ");
        }

        $this->execute("DELETE FROM $translations WHERE [[model]] = $class");
    }

    protected function getQuotedTableName(string $tableName): string
    {
        $db = $this->getDb();
        return $db->quoteTableName($db->getSchema()->getRawTableName($tableName));
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return Yii::$app->getDb()->getSchema()->getTableSchema($table, true)->getColumn($column) !== null;
    }
}
