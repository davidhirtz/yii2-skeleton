<?php

namespace davidhirtz\yii2\skeleton\db\traits;

use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\rbac\DbManager;

trait MigrationTrait
{
    protected function getAuthManager(): DbManager
    {
        $authManager = Yii::$app->getAuthManager();

        if (!$authManager instanceof DbManager) {
            throw new InvalidConfigException('Component "authManager" must be configured before running migration.');
        }

        return $authManager;
    }

    protected function getTableOptions(): ?string
    {
        $db = Yii::$app->getDb();

        if ($db->getDriverName() == 'mysql') {
            return $db->charset == 'utf8mb4'
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
                        if (!in_array($language, $except)) {
                            $type->append("AFTER [[$prevAttribute]]");
                            $prevAttribute = $i18n->getAttributeName($attribute, $language);

                            if (!$tableSchema->getColumn($prevAttribute)) {
                                $this->addColumn($table, $prevAttribute, $type);
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

                    if (!in_array($language, $except) && $tableSchema->getColumn($column)) {
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
}
