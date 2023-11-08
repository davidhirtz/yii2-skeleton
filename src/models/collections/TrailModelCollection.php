<?php

namespace davidhirtz\yii2\skeleton\models\collections;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Yii;
use yii\base\Model;

class TrailModelCollection
{
    private static ?array $_modelClasses = [];

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
            $instance = Yii::createObject($modelName[0]);

            if ($instance instanceof ActiveRecord && $modelId) {
                $values = explode('-', $modelId);
                $keys = count($instance::primaryKey()) == count($values) ? array_combine($instance::primaryKey(), $values) : null;

                static::$_modelClasses[$instance::tableName()][$modelId] ??= $instance::findOne($keys) ?? $instance;
                return static::$_modelClasses[$instance::tableName()][$modelId];
            }

            return $instance;
        });
    }
}
