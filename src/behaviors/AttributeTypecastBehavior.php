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
     * @var array|null the list of nullable attributes to be typecast to `null` if empty. If `null`, nullable
     * attributes will be auto-detected from the table schema.
     */
    public ?array $nullableAttributes = null;

    public function attach($owner): void
    {
        $this->nullableAttributes ??= $this->detectedNullableAttributes($owner);
        parent::attach($owner);
    }

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
        $this->typecastNullableAttributes();
        parent::typecastAttributes($attributeNames);
    }

    protected function typecastNullableAttributes(): void
    {
        foreach ($this->nullableAttributes as $attribute) {
            $this->owner->$attribute = $this->owner->$attribute ?: null;
        }
    }

    protected function detectedNullableAttributes($owner): array
    {
        $attributes = [];

        if ($owner instanceof ActiveRecord) {
            foreach ($owner::getDb()->getSchema()->getTableSchema($owner::tableName())->columns as $column) {
                if ($column->allowNull) {
                    $attributes[] = $column->name;
                }
            }
        }

        return $attributes;
    }
}
