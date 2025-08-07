<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\collections;

use DateTime;
use DateTimeZone;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Throwable;
use Yii;
use yii\base\Model;
use yii\helpers\Inflector;
use yii\validators\BooleanValidator;
use yii\validators\RangeValidator;

class TrailModelCollection
{
    private const string VALUE_TYPE_BOOLEAN = 'bool';
    private const string VALUE_TYPE_RANGE = 'range';
    private const string VALUE_TYPE_DATETIME = 'datetime';

    /**
     * @var ActiveRecord[][]
     */
    private static array $models = [];
    private static array $_modelAttributes = [];

    /**
     * Finds the model based on the given model string. If a model supports `i18n` tables, the corresponding language
     * will be added to the model, separated by "::" like `\davidhirtz\yii2\cms\models\Entry::en_US`.
     *
     * For this reason, the cache key is the table name, rather than the model class name.
     */
    public static function getModelByNameAndId(string $modelName, int|string|null $modelId): ?Model
    {
        $modelName = explode('::', $modelName);
        $language = $modelName[1] ?? Yii::$app->language;

        return Yii::$app->getI18n()->callback($language, function () use ($modelName, $modelId) {
            try {
                $instance = Yii::createObject($modelName[0]);

                return $instance instanceof ActiveRecord && $modelId
                    ? self::getOrFindActiveRecord($instance, $modelId)
                    : $instance;
            } catch (Throwable) {
                return null;
            }
        });
    }

    /**
     * This is the fallback method to format the value based on the attribute name
     */
    public static function formatAttributeValue(Model $model, string $attribute, mixed $value): mixed
    {
        if ($model instanceof ActiveRecord) {
            if ($relation = $model->getRelationFromForeignKey($attribute)) {
                return self::getModelByNameAndId($relation->modelClass, $value);
            }
        }

        switch (self::getDefaultAttributeValues($model)[$attribute] ?? null) {
            case self::VALUE_TYPE_BOOLEAN:
                return $value ? Yii::t('yii', 'Yes') : Yii::t('yii', 'No');

            case self::VALUE_TYPE_DATETIME:
                return is_array($value) && isset($value['date'])
                    ? Yii::$app->getFormatter()->asDatetime(new DateTime($value['date'], new DateTimeZone($value['timezone'] ?? Yii::$app->timeZone)), 'medium')
                    : $value;

            case self::VALUE_TYPE_RANGE:
                $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));

                if ($model->hasMethod($method)) {
                    if ($value = ($model->{$method}()[$value] ?? false)) {
                        // Return string value or "name" key, as a fallback print out the array content
                        return is_string($value) ? $value : ($value['name'] ?? print_r($value, true));
                    }
                }

                return $value;
        }

        return is_array($value) ? print_r($value, true) : (string)$value;
    }

    private static function getOrFindActiveRecord(ActiveRecord $instance, int|string $id): ActiveRecord
    {
        $tableName = $instance::tableName();

        if (!isset(self::$models[$tableName][$id])) {
            $primaryKey = $instance::primaryKey();
            $values = is_string($id) ? explode('-', $id) : $id;
            $keys = count($primaryKey) > 1 && count($primaryKey) === count($values)
                ? array_combine($primaryKey, $values)
                : array_combine($primaryKey, [$id]);

            self::$models[$tableName][$id] = $instance::findOne($keys) ?? new $instance($keys);
        }

        return self::$models[$tableName][$id] ?? $instance;
    }

    /**
     * Cycles through the owner model validators to detect default display values for attribute names.
     */
    private static function getDefaultAttributeValues(Model $model): array
    {
        $className = $model::class;

        if (!isset(self::$_modelAttributes[$className])) {
            $attributes = [];

            $types = [
                self::VALUE_TYPE_BOOLEAN => BooleanValidator::class,
                self::VALUE_TYPE_RANGE => RangeValidator::class,
            ];

            foreach ($model->getValidators() as $validator) {
                foreach ($types as $type => $instance) {
                    if ($validator instanceof $instance) {
                        foreach ((array)$validator->attributes as $attribute) {
                            $attributes[$attribute] = $type;
                        }
                    }
                }
            }

            if ($model instanceof ActiveRecord) {
                $schema = Yii::$app->getDb()->getSchema();
                $columns = $schema->getTableSchema($model::tableName())->columns;

                $dateTypes = [
                    $schema::TYPE_DATE,
                    $schema::TYPE_DATETIME,
                    $schema::TYPE_TIMESTAMP,
                ];

                foreach ($columns as $column) {
                    if (in_array($column->dbType, $dateTypes)) {
                        $attributes[$column->name] = self::VALUE_TYPE_DATETIME;
                    }
                }
            }

            self::$_modelAttributes[$className] = $attributes;
        }

        return self::$_modelAttributes[$className];
    }
}
