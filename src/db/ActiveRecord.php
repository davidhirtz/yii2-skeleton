<?php

namespace davidhirtz\yii2\skeleton\db;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\db\commands\BatchInsertQueryBuild;
use ReflectionClass;
use Yii;
use yii\behaviors\AttributeTypecastBehavior;
use yii\helpers\Inflector;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

/**
 * @method ActiveQuery hasMany($class, array $link)
 * @method ActiveQuery hasOne($class, array $link)
 * @method static static[] findAll($condition)
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    use ModelTrait;

    public const SCENARIO_INSERT = 'insert';
    public const SCENARIO_UPDATE = 'update';

    public const EVENT_CREATE_VALIDATORS = 'afterValidators';

    /**
     * @var array containing the attribute names of attributes which should be used with I18N features
     */
    public array $i18nAttributes = [];

    private bool $_isBatch = false;
    private bool $_isDeleted = false;

    public function beforeValidate(): bool
    {
        $this->typecastAttributes();
        return parent::beforeValidate();
    }

    public function beforeDelete(): bool
    {
        $this->_isDeleted = true;
        return parent::beforeDelete();
    }

    /**
     * @return ActiveQuery<static>
     */
    public static function find(): ActiveQuery
    {
        return Yii::createObject(ActiveQuery::class, [static::class]);
    }

    public static function findOne($condition): ?static
    {
        return $condition === null ? null : parent::findOne($condition);
    }

    public function getRelationFromForeignKey(string $foreignKey, bool $throwException = false): ?ActiveQuery
    {
        /** @var ActiveQuery|null $query */
        $query = $this->getRelation($this->getRelationNameFromForeignKey($foreignKey), $throwException);
        return $query;
    }

    public function getRelationNameFromForeignKey(string $foreignKey): string
    {
        return lcfirst(Inflector::camelize(str_replace('_id', '', $foreignKey)));
    }

    public function refreshRelation(string $name): ActiveRecord|array|null
    {
        /** @var ActiveQuery $query */
        $query = $this->getRelation($name);
        $method = $query->multiple ? 'all' : 'one';

        $related = $query->{$method}();
        $this->populateRelation($name, $related);

        return $related;
    }

    /**
     * Typecasts boolean and numeric validators. This is similar to {@see AttributeTypecastBehavior} but performs the
     * operation before the actual validation to allow the use of {@see \yii\db\ActiveRecord::isAttributeChanged()} in
     * validation. As Yii2 represents floats and decimals as strings, only integer values will be typecast.
     */
    public function typecastAttributes(): void
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof BooleanValidator || ($validator instanceof NumberValidator && $validator->integerOnly)) {
                foreach ((array)$validator->attributes as $attribute) {
                    $this->$attribute = (int)$this->$attribute;
                }
            }

            if ($validator instanceof StringValidator) {
                foreach ((array)$validator->attributes as $attribute) {
                    $this->$attribute = (string)$this->$attribute;
                }
            }
        }

        foreach (static::getDb()->getSchema()->getTableSchema(static::tableName())->columns as $column) {
            if ($column->allowNull && !$this->{$column->name}) {
                $this->{$column->name} = null;
            }
        }
    }

    public function updateAttributesBlameable(array $attributes): int
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                if ($value == 'updated_by_user_id') {
                    $attributes[$value] = Yii::$app->has('user') ? Yii::$app->getUser()->getId() : null;
                    unset($name);
                }

                if ($value == 'updated_at') {
                    $attributes[$value] = new DateTime();
                    unset($name);
                }
            }
        }

        return $this->updateAttributes($attributes);
    }

    public static function batchInsert(array $columns, ?array $rows = null, bool $ignore = false): int
    {
        $query = Yii::createObject(BatchInsertQueryBuild::class, [static::class, ...func_get_args()]);
        return $query->getCommand()->execute();
    }

    /**
     * Extends the default functionality by checking for DateTime objects, which unfortunately cannot be compared by
     * checking identical values using `===` as it always returns `true` even if the date was not changed.
     */
    public function getDirtyAttributes($names = null): array
    {
        return array_filter(parent::getDirtyAttributes($names), function ($name): bool {
            $attribute = $this->getAttribute($name);
            return !$attribute instanceof \DateTime || $this->getOldAttribute($name) != $attribute;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Extends the default functionality by setting $identical to `false` for DateTime objects, which unfortunately
     * cannot be compared by checking identical values using `===` as it always returns `true` even if the date was not
     * changed.
     */
    public function isAttributeChanged($name, $identical = true): bool
    {
        if ($this->getAttribute($name) instanceof \DateTime) {
            $identical = false;
        }

        return parent::isAttributeChanged($name, $identical);
    }

    public function hasChangedAttributes(array $attributeNames, bool $identical = true): bool
    {
        foreach ($attributeNames as $attribute) {
            if ($this->isAttributeChanged($attribute, $identical)) {
                return true;
            }
        }

        return false;
    }

    public function getTraitRules(): array
    {
        $rules = [];

        foreach ($this->getTraitNames() as $traitName) {
            $method = "get{$traitName}Rules";

            if (method_exists($this, $method)) {
                $rules = [...$rules, ...$this->{$method}()];
            }
        }

        return $rules;
    }

    public function getTraitNames(): array
    {
        $traitNames = (new ReflectionClass($this))->getTraitNames();
        return array_map(fn($name) => substr($name, strrpos($name, '\\') + 1), $traitNames);
    }

    public function setIsBatch(bool $isBatch): void
    {
        $this->_isBatch = $isBatch;
    }

    public function getIsBatch(): bool
    {
        return $this->_isBatch;
    }

    public function isDeleted(): bool
    {
        return $this->_isDeleted;
    }

    public function getTraitAttributeLabels(): array
    {
        $attributeLabels = [];

        foreach ($this->getTraitNames() as $traitName) {
            $method = "get{$traitName}AttributeLabels";

            if (method_exists($this, $method)) {
                $attributeLabels = [...$attributeLabels, ...$this->{$method}()];
            }
        }

        return $attributeLabels;
    }

    public function attributeLabels(): array
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
