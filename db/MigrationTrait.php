<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class MigrationTrait.
 * @package davidhirtz\yii2\skeleton\db
 */
trait MigrationTrait
{
    /**
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getTableOptions()
    {
        $db = Yii::$app->getDb();

        if ($db->getDriverName() == 'mysql') {
            return $db->charset == 'utf8mb4' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE InnoDB' : 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE InnoDB';
        }

        throw new InvalidConfigException;
    }

    /**
     * @param string $table
     * @param array|string $attributes
     * @param mixed $except
     */
    public function addI18nColumns($table, $attributes, $except = null)
    {
        if ($attributes) {
            $schema = Yii::$app->getDb()->getSchema();
            $languages = Yii::$app->getI18n()->getLanguages();

            if ($except === null) {
                $except = [Yii::$app->sourceLanguage];
            }

            foreach ((array)$attributes as $attribute) {
                $column = $schema->getTableSchema($table)->getColumn($attribute);
                $prevAttribute = $attribute;

                $type = $schema->createColumnSchemaBuilder($column->type, $column->size)->defaultValue($column->defaultValue);

                if ($column->unsigned) {
                    $type->unsigned();
                }

                if (!$column->allowNull) {
                    $type->notNull();
                }

                foreach ($languages as $language) {
                    if (!in_array($language, $except)) {
                        $type->append("AFTER [[{$prevAttribute}]]");
                        $prevAttribute = Yii::$app->getI18n()->getAttributeName($attribute, $language);

                        $this->addColumn($table, $prevAttribute, $type);
                    }
                }
            }
        }
    }
}