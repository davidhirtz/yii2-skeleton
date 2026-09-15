<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Collections;

use DateTime;
use DateTimeZone;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Definitions\Definition;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
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
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $modelAttributes = [];

    /**
     * Finds the model based on the given class string. If a model supports `i18n` tables, the corresponding language
     * will be added to the model, separated by "::" like `\Hirtz\Cms\Models\Entry::en_US`.
     *
     * For this reason, the cache key is the table name, rather than the model class name.
     */
    public static function getModelByClassAndId(string $modelClass, int|string|null $modelId): ?Model
    {
        $modelClass = explode('::', $modelClass);
        $language = $modelClass[1] ?? Yii::$app->language;

        return Yii::$app->getI18n()->callback($language, function () use ($modelClass, $modelId) {
            try {
                /** @var class-string $class */
                $class = $modelClass[0];
                $instance = Yii::createObject($class);
            } catch (Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                $instance = null;
            }

            return $instance instanceof ActiveRecord && $modelId
                ? self::getOrFindActiveRecord($instance, $modelId)
                : $instance;
        });
    }

    /**
     * The statics outlive the application; `ApplicationTrait::preInitInternal()` resets them, so a test's
     * application does not report the record a previous one loaded before it was renamed.
     */
    public static function reset(): void
    {
        self::$models = [];
        self::$modelAttributes = [];
    }

    /**
     * This is the fallback method to format the value based on the attribute name
     */
    public static function formatAttributeValue(Model $model, string $attribute, mixed $value): mixed
    {
        if ($model instanceof ActiveRecord) {
            $relation = $model->getRelationFromForeignKey($attribute);

            if ($relation) {
                return self::getModelByClassAndId($relation->modelClass, $value);
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
                return self::formatRangeValue($model, $attribute, $value);
        }

        return is_array($value) ? print_r($value, true) : (string)$value;
    }

    /**
     * The resolved definitions before the declaration, as {@see DynamicRangeValidator::getDynamicRange()} and
     * {@see SelectField::getItemsFromModel()} do: `getTypes()` is indexed by offset, `getTypeDefinitions()` by value.
     */
    private static function formatRangeValue(Model $model, string $attribute, mixed $value): mixed
    {
        // A `RangeValidator` with `allowArray` holds a list, which is no array offset and renders on its own.
        if (!$value || !is_scalar($value)) {
            return $value;
        }

        $method = 'get' . Inflector::camelize($attribute) . 'Definitions';

        if (!$model->hasMethod($method)) {
            $method = 'get' . Inflector::camelize(Inflector::pluralize($attribute));
        }

        if (!$model->hasMethod($method)) {
            return $value;
        }

        $item = $model->{$method}()[$value] ?? null;

        if ($item instanceof Definition) {
            return $item->getName();
        }

        // A plain `value => label` map, or the pre-3.0 `['name' => ...]` shape a project may still declare.
        return match (true) {
            $item === null => $value,
            is_array($item) => $item['name'] ?? print_r($item, true),
            default => $item,
        };
    }

    private static function getOrFindActiveRecord(ActiveRecord $instance, int|string $id): ActiveRecord
    {
        $tableName = $instance::tableName();

        if (!isset(self::$models[$tableName][$id])) {
            $values = is_string($id) ? explode('-', $id) : $id;
            $keys = [];

            foreach ($instance::primaryKey() as $index => $key) {
                $keys[$key] = $values[$index] ?? $values;
            }

            self::$models[$tableName][$id] = $instance::findOne($keys) ?? new $instance($keys);
        }

        return self::$models[$tableName][$id];
    }

    /**
     * Cycles through the owner model validators to detect default display values for attribute names.
     *
     * @return array<string, mixed>
     */
    private static function getDefaultAttributeValues(Model $model): array
    {
        $className = $model::class;

        if (!isset(self::$modelAttributes[$className])) {
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
                    if (in_array($column->dbType, $dateTypes, true)) {
                        $attributes[$column->name] = self::VALUE_TYPE_DATETIME;
                    }
                }
            }

            self::$modelAttributes[$className] = $attributes;
        }

        return self::$modelAttributes[$className];
    }
}
