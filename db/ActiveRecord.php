<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\db\Connection;

/**
 * Class ActiveRecord
 * @package davidhirtz\yii2\skeleton\db
 *
 * @method ActiveQuery hasMany($class, array $link)
 * @method ActiveQuery hasOne($class, array $link)
 * @method static ActiveRecord|\yii\db\ActiveRecord findOne($condition)
 * @method static ActiveRecord[] findAll($condition)
 * @method static Connection getDb()
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
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
     * @var array
     */
    public $i18nAttributes = [];

    /**
     * @var array containing cached active attributes, reset on setScenario
     */
    private $_activeAttributes;

    /**
     * @var array containing cached safe attributes, reset on setScenario
     */
    private $_safeAttributes;

    /**
     * @var array containing cached scenarios
     */
    private $_scenarios;

    /**
     * @var bool
     */
    private $_isDeleted = false;

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
     * Sets deleted flag.
     */
    public function beforeDelete()
    {
        $this->_isDeleted = true;
        return parent::beforeDelete();
    }

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * Reloads the relation and returns the record.
     *
     * @param string $name
     * @return ActiveRecord|null
     */
    public function refreshRelation($name)
    {
        $this->populateRelation($name, $related = $this->getRelation($name)->one());
        return $related;
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
     * @return array
     */
    public function activeAttributes()
    {
        if ($this->_activeAttributes === null) {
            $this->_activeAttributes = parent::activeAttributes();
        }

        return $this->_activeAttributes;
    }

    /**
     * @return array
     */
    public function safeAttributes()
    {
        if ($this->_safeAttributes === null) {
            $this->_safeAttributes = parent::safeAttributes();
        }

        return $this->_safeAttributes;
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        if ($this->_scenarios === null) {
            $this->_scenarios = parent::scenarios();
        }

        return $this->_scenarios;
    }

    /**
     * @inheritDoc
     */
    public function setScenario($value)
    {
        $this->_activeAttributes = $this->_safeAttributes = null;
        parent::setScenario($value);
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->_isDeleted;
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