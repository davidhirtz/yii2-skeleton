<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\ActiveRecordInterface;
use yii\db\Connection;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;

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
    public const SCENARIO_INSERT = 'insert';
    public const SCENARIO_UPDATE = 'update';

    public const STATUS_DEFAULT = 3;
    public const STATUS_DISABLED = 0;
    public const STATUS_DRAFT = 1;
    public const STATUS_ENABLED = 3;

    public const TYPE_DEFAULT = 1;

    /**
     * @var array containing a the attribute names of attributes which should be used with I18N features such as
     * {@link ActiveRecord::getI18nAttribute()}, {@link ActiveRecord::getI18nRules()}, etc.
     */
    public $i18nAttributes = [];

    /**
     * @var array {@see ActiveRecord::activeAttributes()}
     */
    private $_activeAttributes;

    /**
     * @var array {@see ActiveRecord::safeAttributes()}
     */
    private $_safeAttributes;

    /**
     * @var array {@see ActiveRecord::scenarios()}
     */
    private $_scenarios;

    /**
     * @var bool whether the current operation is part of a more complex process, this can be used to postpone conditional
     * updates after save or delete. See {@link ActiveRecord::getIsBatch()}.
     */
    private $_isBatch = false;

    /**
     * @var bool whether the record was deleted, this is set in {@link ActiveRecord::afterDelete()} and can be used
     * via {@link ActiveRecord::isDeleted()}.
     */
    private $_isDeleted = false;

    /**
     * @param string $attribute
     * @return false
     */
    public function addInvalidAttributeError($attribute): bool
    {
        $this->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));

        return false;
    }

    /**
     * @inheritDoc
     */
    public function beforeValidate()
    {
        $this->typecastAttributes();
        return parent::beforeValidate();
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
     * Typecasts boolean and numeric validators. This is similar to {@link AttributeTypecastBehavior} but performs the
     * operation before the actual validation to allow the use of {@link \yii\db\ActiveRecord::isAttributeChanged()} in
     * validation. As Yii2 represents floats and decimals as strings only integer values will be typecast.
     */
    public function typecastAttributes()
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof BooleanValidator || ($validator instanceof NumberValidator && $validator->integerOnly)) {
                foreach ((array)$validator->attributes as $attribute) {
                    $this->$attribute = (int)$this->$attribute;
                }
            }
        }

        foreach (static::getDb()->getSchema()->getTableSchema(static::tableName())->columns as $column) {
            if ($column->allowNull && !$this->{$column->name}) {
                $this->{$column->name} = null;
            }
        }
    }

    /**
     * Reloads the relation and returns the record.
     *
     * @param string $name
     * @return ActiveRecordInterface
     */
    public function refreshRelation($name)
    {
        $this->populateRelation($name, $related = $this->getRelation($name)->one());
        return $related;
    }

    /**
     * Updates the order attribute of models by given order.
     *
     * ```php
     * davidhirtz\yii2\skeleton\db\ActiveRecord::updatePosition($asset, array_flip(Yii::$app->getRequest()->post('param')))
     * ```
     *
     * @param ActiveRecord[] $models
     * @param array $order containing the primary keys in new order
     * @param string $attribute the order attribute
     * @param string|null $index key attribute name
     * @return int
     */
    public static function updatePosition($models, $order = [], $attribute = 'position', $index = null)
    {
        $rowsUpdated = 0;

        foreach ($models as $model) {
            $primaryKey = $model->getPrimaryKey(true);
            $position = ArrayHelper::getValue($order, $index ? $primaryKey[$index] : current($primaryKey), 0) + 1;

            if ($position != $model->getAttribute($attribute)) {
                $rowsUpdated += $model::updateAll([$attribute => $position], $primaryKey);
            }
        }

        return $rowsUpdated;
    }

    /**
     * @param array $columns the column names
     * @param array|null $rows the rows to be batch inserted into the table
     * @param bool $ignore whether records should be inserted regardless of previous errors or existing primary keys
     * @return int number of rows affected by the execution
     */
    public static function batchInsert($columns, $rows = null, $ignore = false)
    {
        if ($rows === null) {
            $rows = $columns;
            $columns = array_keys(current($columns));
        }

        $query = static::getDb()->createCommand()
            ->batchInsert(static::tableName(), $columns, $rows);

        if ($ignore) {
            if (static::getDb()->getDriverName() !== 'mysql') {
                throw new NotSupportedException(static::class . "::batchInsert does not support the option `ignore` for this database driver.");
            }

            $sql = $query->getRawSql();
            $sql = 'INSERT IGNORE' . mb_substr($sql, 0, strlen('INSERT'), Yii::$app->charset);

            return static::getDb()->createCommand($sql)->execute();
        }

        return $query->execute();
    }

    /**
     * This method is in place to avoid endless calls to {@link \yii\db\ActiveRecord::activeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     *
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
     * This method is in place to avoid endless calls to {@link \yii\db\ActiveRecord::safeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     *
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
     * This method is in place to avoid endless calls to {@link \yii\db\ActiveRecord::scenarios()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     *
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
        $this->_activeAttributes = null;
        $this->_safeAttributes = null;
        $this->_scenarios = null;

        parent::setScenario($value);
    }

    /**
     * @param bool $isBatch
     */
    public function setIsBatch(bool $isBatch): void
    {
        $this->_isBatch = $isBatch;
    }

    /**
     * @return bool
     */
    public function getIsBatch(): bool
    {
        return $this->_isBatch;
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