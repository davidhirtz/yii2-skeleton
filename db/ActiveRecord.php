<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\InvalidCallException;

/**
 * Class ActiveRecord.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @method static ActiveRecord|\yii\db\ActiveRecord findOne($condition)
 * @method static ActiveRecord[] findAll($condition)
 * @method static ActiveQuery find()
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @var array
     */
    public $i18nAttributes = [];

    /**
     * Scenarios.
     */
    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    /***********************************************************************
     * Methods.
     ***********************************************************************/

    /**
     * @param string $attribute
     * @return bool
     */
    public function addInvalidAttributeError($attribute): bool
    {
        $this->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));

        return false;
    }

    /**
     * @param string $name
     */
    public function refreshRelation($name)
    {
        $this->populateRelation($name, $this->getRelation($name)->one());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function relationIsChanged($name)
    {
        $relation = $this->getRelation($name);

        if ($relation->multiple) {
            throw new InvalidCallException('ActiveRecord::relationIsChanged cannot be called on multiple related records.');
        }

        return !$this->isRelationPopulated($name) || !$this->{$name} instanceof $relation->modelClass || $this->{$name}->{key($relation->link)} != $this->{current($relation->link)};
    }

    /**
     * @param ActiveRecord[] $models
     * @param array $order
     * @param string $attribute
     * @param string $index
     */
    public static function updatePosition($models, $order = [], $attribute = 'position', $index = null)
    {
        foreach ($models as $model) {
            $primaryKey = $model->getPrimaryKey(true);
            $position = ArrayHelper::getValue($order, $index ? $primaryKey[$index] : current($primaryKey), 0);

            if ($position != $model->getAttribute($attribute)) {
                $model::updateAll([$attribute => $position], $primaryKey);
            }
        }
    }

    /**
     * @param array $columns the column names
     * @param array $rows the rows to be batch inserted into the table
     * @return int number of rows affected by the execution.
     * @throws \yii\db\Exception
     */
    public static function batchInsert($columns, $rows)
    {
        return static::getDb()->createCommand()
            ->batchInsert(static::tableName(), $columns, $rows)
            ->execute();
    }
}