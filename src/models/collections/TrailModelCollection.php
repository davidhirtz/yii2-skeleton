<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\collections;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Throwable;
use Yii;
use yii\base\Model;

final class TrailModelCollection
{
    private static array $records = [];

    /**
     * Finds the model based on the given model string. If a model supports `i18n` tables, the corresponding language
     * will be added to the model, separated by "::" like `\app\models\Entry::en_US`.
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

    private static function getOrFindActiveRecord(ActiveRecord $instance, int|string $id): ActiveRecord
    {
        $tableName = $instance::tableName();

        if (!isset(self::$records[$tableName][$id])) {
            $primaryKey = $instance::primaryKey();
            $values = is_string($id) ? explode('-', $id) : $id;
            $keys = count($primaryKey) > 1 && count($primaryKey) === count($values)
                ? array_combine($primaryKey, $values)
                : array_combine($primaryKey, [$id]);

            self::$records[$tableName][$id] = $instance::findOne($keys) ?? new $instance($keys);
        }

        return self::$records[$tableName][$id] ?? $instance;
    }
}
