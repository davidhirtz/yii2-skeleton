<?php

namespace davidhirtz\yii2\skeleton\db;

use ArrayObject;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\db\commands\BatchInsertQueryBuild;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\behaviors\AttributeTypecastBehavior;
use yii\helpers\Inflector;
use yii\log\Logger;
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
    public const SCENARIO_INSERT = 'insert';
    public const SCENARIO_UPDATE = 'update';

    public const EVENT_CREATE_VALIDATORS = 'afterValidators';

    public const STATUS_DEFAULT = 3;
    public const STATUS_DISABLED = 0;
    public const STATUS_DRAFT = 1;
    public const STATUS_ENABLED = 3;

    public const TYPE_DEFAULT = 1;

    /**
     * @var array containing the attribute names of attributes which should be used with I18N features
     */
    public array $i18nAttributes = [];

    private ?ArrayObject $_validators = null;
    private ?array $_activeAttributes = null;
    private ?array $_safeAttributes = null;
    private ?array $_scenarios = null;
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

    public static function create(): static
    {
        return Yii::createObject(static::class);
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

    /**
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function getRelationFromForeignKey(string $foreignKey, bool $throwException = false): ?ActiveQuery
    {
        $relation = lcfirst(Inflector::camelize(str_replace('_id', '', $foreignKey)));
        return $this->getRelation($relation, $throwException);
    }

    public function refreshRelation(string $name): ActiveRecord|array
    {
        $query = $this->getRelation($name);
        $method = $query->multiple ? 'all' : 'one';
        $this->populateRelation($name, $related = $query->{$method}());

        return $related;
    }


    /**
     * Typecasts boolean and numeric validators. This is similar to {@see AttributeTypecastBehavior} but performs the
     * operation before the actual validation to allow the use of {@see \yii\db\ActiveRecord::isAttributeChanged()} in
     * validation. As Yii2 represents floats and decimals, as strings only integer values will be typecast.
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

    public function addInvalidAttributeError(string $attribute): bool
    {
        $this->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getAttributeLabel($attribute),
        ]));

        return false;
    }

    /**
     * @noinspection PhpUnused
     */
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

    public function logErrors(?string $message = null, int $level = Logger::LEVEL_WARNING, string $category = 'application'): void
    {
        if (!$message) {
            $modelName = Inflector::camel2words($this->formName());
            $message = ("$modelName {$this->getPrimaryKey()} could not be " . ($this->getIsNewRecord() ? 'inserted.' : 'updated.'));
        }

        if ($errors = $this->getErrors()) {
            $message .= "\n" . print_r($errors, true);
        }

        Yii::getLogger()->log($message, $level, $category);
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
     *
     * Furthermore, this method now accepts an array to allow checking multiple attributes at once.
     */
    public function isAttributeChanged($name, $identical = true): bool
    {
        if (is_array($name)) {
            foreach ($name as $attribute) {
                if ($this->isAttributeChanged($attribute, $identical)) {
                    return true;
                }
            }

            return false;
        }

        if ($this->getAttribute($name) instanceof \DateTime) {
            $identical = false;
        }

        return parent::isAttributeChanged($name, $identical);
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
        return array_map(fn ($name) => substr($name, strrpos($name, '\\') + 1), $traitNames);
    }

    /**
     * Overrides original method by triggering {@see static::EVENT_CREATE_VALIDATORS} event. This enables attached
     * behaviors to manipulate {@see Model::rules()} by modifying the array object returned by
     * {@see Model::getValidators()}.
     *
     * This would be more fitting in {@see Model::rules()}. I might add a pull request... If this is added to Yii2, the
     * override can be removed. {@link https://github.com/yiisoft/yii2/issues/5438}
     */
    public function getValidators(): ArrayObject
    {
        if ($this->_validators === null) {
            $this->_validators = $this->createValidators();
            $this->trigger(static::EVENT_CREATE_VALIDATORS);
        }

        return $this->_validators;
    }


    /**
     * This method is in place to avoid endless calls to {@see \yii\db\ActiveRecord::activeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function activeAttributes(): array
    {
        $this->_activeAttributes ??= parent::activeAttributes();
        return $this->_activeAttributes;
    }

    /**
     * This method is in place to avoid excessive calls to {@see \yii\db\ActiveRecord::safeAttributes()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function safeAttributes(): array
    {
        $this->_safeAttributes ??= parent::safeAttributes();
        return $this->_safeAttributes;
    }

    /**
     * This method is in place to avoid endless calls to {@see \yii\db\ActiveRecord::scenarios()}.
     * If this method's results are cached in a future Yii2 version, this can be removed.
     */
    public function scenarios(): array
    {
        $this->_scenarios ??= parent::scenarios();
        return $this->_scenarios;
    }

    public function setScenario($value): void
    {
        $this->_activeAttributes = null;
        $this->_safeAttributes = null;
        $this->_scenarios = null;

        parent::setScenario($value);
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
