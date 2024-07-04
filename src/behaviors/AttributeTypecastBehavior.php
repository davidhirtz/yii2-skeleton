<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\StringHelper;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;

/**
 * This is a rewrite of {@see \yii\behaviors\AttributeTypecastBehavior}.
 *
 * The original behavior auto-detects attributes on attaching the behavior. This is not ideal for performance and also
 * makes it impossible to manipulate the validators by other behaviors, as the validators are loaded before the actual
 * validation is performed.
 *
 * Furthermore, this behavior can typecast attributes to `null` if they are empty and nullable, which is auto-detected
 * from the table schema. Boolean values are typecast as integers, which is how they are stored in the database.
 *
 * On default, the behavior only performs typecasting before validation, which makes skipping rules based on changed
 * attributes possible. But it can be configured to perform typecasting after validation, before saving, after saving
 * and after finding, to stay consistent with the original class.
 *
 * @property array $attributeTypes {@see static::setAttributeTypes()}
 * @property array $nullableAttributes {@see static::setNullableAttributes()}
 *
 * @property ActiveRecord|Model $owner
 */
class AttributeTypecastBehavior extends Behavior
{
    final public const TYPE_INTEGER = 'integer';
    final public const TYPE_FLOAT = 'float';
    final public const TYPE_BOOLEAN = 'boolean';
    final public const TYPE_STRING = 'string';

    /**
     * @var bool whether to skip typecasting of `null` values
     */
    public bool $skipOnNull = true;

    /**
     * @var bool whether to typecast the attributes before validation.
     * This allows the use of {@see ActiveRecord::isAttributeChanged()} in validation.
     */
    public bool $typecastBeforeValidate = true;

    /**
     * @var bool whether to perform typecasting after owner model validation.
     */
    public bool $typecastAfterValidate = true;

    /**
     * @var bool whether to perform typecasting before saving the owner model (insert or update).
     */
    public bool $typecastBeforeSave = false;

    /**
     * @var bool whether to perform typecasting after saving owner model (insert or update).
     */
    public bool $typecastAfterSave = false;

    /**
     * @var bool whether to perform typecasting after retrieving owner model data from the database.
     */
    public bool $typecastAfterFind = false;

    /**
     * @var bool whether to typecast boolean values as integers. Defaults to `true` which matches boolean values after
     * they have been retrieved from the database.
     */
    public bool $typecastBooleanAsInteger = true;

    /**
     * @var array|null the list of nullable attributes to be typecast to `null` if empty. If `null`, nullable
     * attributes will be auto-detected from the table schema.
     */
    private ?array $_attributeTypes = null;

    /**
     * @var array|null the list of nullable attributes to be typecast to `null` if empty. If `null`, nullable
     * attributes will be auto-detected from the table schema.
     */
    private ?array $_nullableAttributes = null;

    private static array $_autoDetectedAttributeTypes = [];
    private static array $_autoDetectedNullableAttributes = [];

    public function events(): array
    {
        $events = [];

        if ($this->typecastAfterFind) {
            $events[BaseActiveRecord::EVENT_AFTER_FIND] = $this->afterFind(...);
        }

        if ($this->typecastAfterValidate) {
            $events[Model::EVENT_AFTER_VALIDATE] = $this->afterValidate(...);
        }
        if ($this->typecastBeforeSave) {
            $events[BaseActiveRecord::EVENT_BEFORE_INSERT] = $this->beforeSave(...);
            $events[BaseActiveRecord::EVENT_BEFORE_UPDATE] = $this->beforeSave(...);
        }
        if ($this->typecastAfterSave) {
            $events[BaseActiveRecord::EVENT_AFTER_INSERT] = $this->afterSave(...);
            $events[BaseActiveRecord::EVENT_AFTER_UPDATE] = $this->afterSave(...);
        }

        if ($this->typecastBeforeValidate) {
            $events[BaseActiveRecord::EVENT_BEFORE_VALIDATE] = $this->beforeValidate(...);
        }

        return $events;
    }

    public function beforeValidate(): void
    {
        $this->typecastAttributes();
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterValidate(Event $event): void
    {
        if (!$this->owner->hasErrors()) {
            $this->typecastAttributes();
        }
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeSave(ModelEvent $event): void
    {
        $this->typecastAttributes();
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterSave(AfterSaveEvent $event): void
    {
        $this->typecastAttributes();
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterFind(Event $event): void
    {
        $this->typecastAttributes();
        $this->resetOldAttributes();
    }

    protected function resetOldAttributes(): void
    {
        if ($this->owner instanceof ActiveRecord) {
            $attributes = array_keys($this->getAttributeTypes());

            foreach ($attributes as $attribute) {
                if ($this->owner->canSetOldAttribute($attribute)) {
                    $this->owner->setOldAttribute($attribute, $this->owner->{$attribute});
                }
            }
        }
    }

    public function typecastAttributes(?array $attributeNames = null): void
    {
        $attributeNames ??= $this->owner->attributes();

        foreach ($attributeNames as $attribute) {
            $value = $this->owner->$attribute;

            if ($this->skipOnNull && $value === null) {
                continue;
            }

            $newValue = $this->typecastAttribute($attribute);

            if ($newValue !== $value) {
                $this->owner->$attribute = $newValue;
            }
        }
    }

    protected function typecastAttribute(string $attributeName): mixed
    {
        $type = $this->getAttributeTypes()[$attributeName] ?? null;
        $value = $this->owner->$attributeName;

        if (!is_scalar($type)) {
            return $type ? call_user_func($type, $value) : $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = $value->__toString();
        }

        if ($this->isEmpty($value) && in_array($attributeName, $this->getNullableAttributes())) {
            return null;
        }

        return match ($type) {
            self::TYPE_INTEGER => (int)$value,
            self::TYPE_FLOAT => (float)$value,
            self::TYPE_BOOLEAN => $this->typecastBooleanAsInteger ? (int)$value : (bool)$value,
            self::TYPE_STRING => is_float($value) ? StringHelper::floatToString($value) : (string)$value,
            default => throw new InvalidArgumentException("Unsupported type '$type'"),
        };
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === [] || $value === '';
    }

    public function getAttributeTypes(): ?array
    {
        if ($this->_attributeTypes === null) {
            self::$_autoDetectedAttributeTypes[$this->owner::class] ??= $this->detectAttributeTypes();
            $this->_attributeTypes = self::$_autoDetectedAttributeTypes[$this->owner::class];
        }

        return $this->_attributeTypes;
    }

    public function setAttributeTypes(?array $attributeTypes): void
    {
        $this->_attributeTypes = $attributeTypes;
    }

    protected function detectAttributeTypes(): array
    {
        /** @noinspection DuplicatedCode */
        $attributeTypes = [];

        foreach ($this->owner->getValidators() as $validator) {
            $type = null;

            if ($validator instanceof BooleanValidator) {
                $type = self::TYPE_BOOLEAN;
            } elseif ($validator instanceof NumberValidator) {
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_FLOAT;
            } elseif ($validator instanceof StringValidator) {
                $type = self::TYPE_STRING;
            }

            if ($type !== null) {
                $attributeTypes += array_fill_keys($validator->getAttributeNames(), $type);
            }
        }

        return $attributeTypes;
    }

    public function getNullableAttributes(): ?array
    {
        if ($this->_nullableAttributes === null) {
            self::$_autoDetectedNullableAttributes[$this->owner::class] ??= $this->detectNullableAttributes();
            $this->_nullableAttributes = self::$_autoDetectedNullableAttributes[$this->owner::class];
        }

        return $this->_nullableAttributes;
    }

    public function setNullableAttributes(?array $nullableAttributes): void
    {
        $this->_nullableAttributes = $nullableAttributes;
    }

    protected function detectNullableAttributes(): array
    {
        if (!$this->owner instanceof ActiveRecord) {
            return [];
        }

        $columns = $this->owner::getDb()->getSchema()->getTableSchema($this->owner::tableName())?->columns ?? [];
        $nullableAttributes = [];

        foreach ($columns as $column) {
            if ($column->allowNull) {
                $nullableAttributes[] = $column->name;
            }
        }

        return $nullableAttributes;
    }

    public static function clearAutoDetectedAttributeTypes(): void
    {
        self::$_autoDetectedAttributeTypes = [];
        self::$_autoDetectedNullableAttributes = [];
    }
}
