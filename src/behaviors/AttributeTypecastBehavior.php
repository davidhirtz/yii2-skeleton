<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use yii\db\ActiveRecord;

class AttributeTypecastBehavior extends \yii\behaviors\AttributeTypecastBehavior
{
    /**
     * @var bool whether to typecast the attributes before validation.
     * This allows the use of {@see ActiveRecord::isAttributeChanged()} in validation.
     */
    public bool $typecastBeforeValidate = true;

    /**
     * @var bool whether to typecast boolean values as integers. Defaults to `true` which matches boolean values after
     * they have been retrieved from the database.
     */
    public bool $castBooleansAsInt = true;

    /**
     * @var array|null the list of nullable attributes to be typecast to `null` if empty. If `null`, nullable
     * attributes will be auto-detected from the table schema.
     */
    public ?array $nullableAttributes = null;

    private static array $_autoDetectedNullableAttributes = [];

    public function events(): array
    {
        $events = parent::events();

        if ($this->typecastBeforeValidate) {
            $events[ActiveRecord::EVENT_BEFORE_VALIDATE] = $this->beforeValidate(...);
        }

        return $events;
    }

    public function beforeValidate(): void
    {
        $this->typecastAttributes();
    }

    public function typecastAttributes($attributeNames = null): void
    {
        parent::typecastAttributes($attributeNames);
        $this->typecastNullableAttributes();
    }

    protected function typecastValue($value, $type)
    {
        $value = parent::typecastValue($value, $type);
        return $this->castBooleansAsInt && $type === self::TYPE_BOOLEAN ? (int)$value : $value;
    }

    public function typecastNullableAttributes(): void
    {
        $this->nullableAttributes ??= $this->detectedNullableAttributes();

        foreach ($this->nullableAttributes as $attribute) {
            $this->owner->$attribute = $this->owner->$attribute ?: null;
        }
    }

    protected function detectedNullableAttributes(): array
    {
        if (!isset(self::$_autoDetectedNullableAttributes[$this->owner::class])) {
            if ($this->owner instanceof ActiveRecord) {
                $columns = $this->owner::getDb()->getSchema()->getTableSchema($this->owner::tableName())?->columns ?? [];
                self::$_autoDetectedNullableAttributes[$this->owner::class] = [];

                foreach ($columns as $column) {
                    if ($column->allowNull) {
                        self::$_autoDetectedNullableAttributes[$this->owner::class][] = $column->name;
                    }
                }
            }
        }

        return self::$_autoDetectedNullableAttributes[$this->owner::class];
    }

    public static function clearAutoDetectedAttributeTypes(): void
    {
        self::$_autoDetectedNullableAttributes = [];
        parent::clearAutoDetectedAttributeTypes();
    }
}
