<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * Class ActiveRecord.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @method ActiveQuery hasMany($class, array $link)
 * @method ActiveQuery hasOne($class, array $link)
 * @method static ActiveRecord|\yii\db\ActiveRecord findOne($condition)
 * @method static ActiveRecord[] findAll($condition)
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @var array
     */
    public $i18nAttributes = [];

    /**
     * Constants.
     */
    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    const STATUS_DEFAULT = 3;
    const STATUS_DISABLED = 0;
    const STATUS_DRAFT = 1;
    const STATUS_ENABLED = 3;

    const TYPE_DEFAULT = 1;

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
     * @return ActiveQuery
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * @param string $name
     * @return ActiveRecord|null
     */
    public function refreshRelation($name)
    {
        $this->populateRelation($name, $related = $this->getRelation($name)->one());
        return $related;
    }

    /**
     * @param string $name
     * @return bool
     */
//    public function relationIsChanged($name)
//    {
//        $relation = $this->getRelation($name);
//
//        if ($relation->multiple) {
//            throw new InvalidCallException('ActiveRecord::relationIsChanged cannot be called on multiple related records.');
//        }
//
//        return !$this->isRelationPopulated($name) || !$this->{$name} instanceof $relation->modelClass || $this->{$name}->{key($relation->link)} != $this->{current($relation->link)};
//    }

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
            $position = ArrayHelper::getValue($order, $index ? $primaryKey[$index] : current($primaryKey), 0) + 1;

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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeleton', 'ID'),
            'status' => Yii::t('skeleton', 'Status'),
            'type' => Yii::t('skeleton', 'Type'),
            'updated_by_user_id' => Yii::t('skeleton', 'User'),
            'updated_at' => Yii::t('skeleton', 'Last Update'),
            'created_at' => Yii::t('skeleton', 'Created'),
        ];
    }
}