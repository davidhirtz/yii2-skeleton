<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord as SkeletonActiveRecord;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\Interfaces\AttributeTypeInterface;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\ColumnSchema;
use yii\db\Schema;
use yii\helpers\StringHelper;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;
use Closure;
use Stringable;

/**
 * Casts the attributes to the type their validators describe when they enter the record (after
 * {@see SkeletonActiveRecord::load()}) and before they are validated and written, so rules, `isAttributeChanged()` and
 * the dirty attributes compare what the database returns. Types come from the validators, `null` for an empty value on
 * a nullable column and the scale of a `decimal` column from the table schema.
 *
 * A value is only cast when nothing is lost: `"abc"` stays a string for the `integer` rule to reject. `null` is never
 * cast. A boolean is an integer and a `decimal` column a string at the column's scale, which is what MySQL returns.
 *
 * A model's own `beforeSave()` runs before the cast when it assigns ahead of `parent::beforeSave()`, which triggers the
 * event, but only `save(false)` reaches it with anything uncast.
 *
 * @property array<string, mixed>|null $attributeTypes {@see static::setAttributeTypes()}
 * @property list<string>|null $nullableAttributes {@see static::setNullableAttributes()}
 *
 * @extends Behavior<ActiveRecord|Model>
 */
class AttributeTypecastBehavior extends Behavior
{
    final public const string TYPE_INTEGER = 'integer';
    final public const string TYPE_FLOAT = 'float';
    final public const string TYPE_BOOLEAN = 'boolean';
    final public const string TYPE_STRING = 'string';

    /**
     * @var array<string, mixed>|null the attribute types, auto-detected from the validators when `null`.
     */
    private ?array $attributeTypes = null;

    /**
     * @var list<string>|null the attributes cast to `null` when empty, auto-detected from the table schema when `null`.
     */
    private ?array $nullableAttributes = null;

    /**
     * @var array<string, int>|null the scale of each `decimal` column
     */
    private ?array $decimalScales = null;

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $autoDetectedAttributeTypes = [];

    /**
     * @var array<string, list<string>>
     */
    private static array $autoDetectedNullableAttributes = [];

    /**
     * @var array<string, array<string, int>>
     */
    private static array $autoDetectedDecimalScales = [];

    /**
     * @return array<string, string|Closure>
     */
    #[\Override]
    public function events(): array
    {
        $handler = fn () => $this->typecastAttributes();

        return [
            SkeletonActiveRecord::EVENT_AFTER_LOAD => $handler,
            Model::EVENT_BEFORE_VALIDATE => $handler,
            BaseActiveRecord::EVENT_BEFORE_INSERT => $handler,
            BaseActiveRecord::EVENT_BEFORE_UPDATE => $handler,
        ];
    }

    /**
     * @param list<string>|null $attributeNames
     */
    public function typecastAttributes(?array $attributeNames = null): void
    {
        $attributeNames ??= $this->owner instanceof SkeletonActiveRecord
            ? $this->owner->getColumnAttributes()
            : $this->owner->attributes();

        foreach ($attributeNames as $attribute) {
            $value = $this->owner->$attribute;

            if ($value === null) {
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

        if ($value instanceof Stringable) {
            $value = (string)$value;
        }

        if ($this->isEmpty($value) && in_array($attributeName, $this->getNullableAttributes(), true)) {
            return null;
        }

        return match ($type) {
            self::TYPE_INTEGER => $this->typecastInteger($value),
            self::TYPE_FLOAT => $this->typecastFloat($value, $this->getDecimalScales()[$attributeName] ?? null),
            self::TYPE_BOOLEAN => $this->typecastBoolean($value),
            self::TYPE_STRING => $this->typecastString($value),
            default => throw new InvalidArgumentException("Unsupported type '$type'"),
        };
    }

    protected function typecastInteger(mixed $value): mixed
    {
        return match (true) {
            $value === '', is_bool($value) => (int)$value,
            is_float($value) => floor($value) === $value && abs($value) < PHP_INT_MAX ? (int)$value : $value,
            is_string($value) => preg_match('/^\s*[+-]?\d+\s*$/', $value) ? (int)$value : $value,
            default => $value,
        };
    }

    protected function typecastFloat(mixed $value, ?int $scale): mixed
    {
        if ($value === '' || is_bool($value) || is_int($value) || is_float($value) || is_numeric($value)) {
            return $scale !== null ? number_format((float)$value, $scale, '.', '') : (float)$value;
        }

        return $value;
    }

    protected function typecastBoolean(mixed $value): mixed
    {
        return in_array($value, [true, false, 0, 1, '0', '1', ''], true) ? (int)$value : $value;
    }

    protected function typecastString(mixed $value): mixed
    {
        return match (true) {
            is_float($value) => StringHelper::floatToString($value),
            is_scalar($value) => (string)$value,
            default => $value,
        };
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === [] || $value === '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttributeTypes(): ?array
    {
        if ($this->attributeTypes === null) {
            self::$autoDetectedAttributeTypes[$this->owner::class] ??= $this->detectAttributeTypes();
            $this->attributeTypes = self::$autoDetectedAttributeTypes[$this->owner::class];
        }

        return $this->attributeTypes;
    }

    /**
     * @param array<string, mixed>|null $attributeTypes
     */
    public function setAttributeTypes(?array $attributeTypes): void
    {
        $this->attributeTypes = $attributeTypes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function detectAttributeTypes(): array
    {
        /** @noinspection DuplicatedCode */
        $attributeTypes = [];

        foreach ($this->owner->getValidators() as $validator) {
            $type = null;

            if ($validator instanceof AttributeTypeInterface) {
                $type = $validator->getAttributeType();
            } elseif ($validator instanceof BooleanValidator) {
                // A rule carrying values of its own does not describe a boolean column — it accepts `yes` and
                // `no`, which `(int)` would both store as `0`.
                $type = $validator->trueValue === '1' && $validator->falseValue === '0'
                    ? self::TYPE_BOOLEAN
                    : null;
            } elseif ($validator instanceof NumberValidator) {
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_FLOAT;
            } elseif ($validator instanceof DynamicRangeValidator) {
                $type = $validator->integerOnly ? self::TYPE_INTEGER : self::TYPE_STRING;
            } elseif ($validator instanceof StringValidator) {
                $type = self::TYPE_STRING;
            }

            if ($type !== null) {
                $attributeTypes += array_fill_keys($validator->getAttributeNames(), $type);
            }
        }

        return $attributeTypes;
    }

    /**
     * @return list<string>|null
     */
    public function getNullableAttributes(): ?array
    {
        if ($this->nullableAttributes === null) {
            self::$autoDetectedNullableAttributes[$this->owner::class] ??= $this->detectNullableAttributes();
            $this->nullableAttributes = self::$autoDetectedNullableAttributes[$this->owner::class];
        }

        return $this->nullableAttributes;
    }

    /**
     * @param list<string>|null $nullableAttributes
     */
    public function setNullableAttributes(?array $nullableAttributes): void
    {
        $this->nullableAttributes = $nullableAttributes;
    }

    /**
     * @return list<string>
     */
    protected function detectNullableAttributes(): array
    {
        $nullableAttributes = [];

        foreach ($this->getColumnSchemas() as $column) {
            if ($column->allowNull) {
                $nullableAttributes[] = $column->name;
            }
        }

        return $nullableAttributes;
    }

    /**
     * @return array<string, int>
     */
    protected function getDecimalScales(): array
    {
        if ($this->decimalScales === null) {
            self::$autoDetectedDecimalScales[$this->owner::class] ??= $this->detectDecimalScales();
            $this->decimalScales = self::$autoDetectedDecimalScales[$this->owner::class];
        }

        return $this->decimalScales;
    }

    /**
     * @return array<string, int>
     */
    protected function detectDecimalScales(): array
    {
        $scales = [];

        foreach ($this->getColumnSchemas() as $column) {
            if ($column->type === Schema::TYPE_DECIMAL && $column->scale !== null) {
                $scales[$column->name] = $column->scale;
            }
        }

        return $scales;
    }

    /**
     * @return array<string, ColumnSchema>
     */
    private function getColumnSchemas(): array
    {
        if (!$this->owner instanceof ActiveRecord) {
            return [];
        }

        return $this->owner::getDb()->getSchema()->getTableSchema($this->owner::tableName())->columns ?? [];
    }

    public static function clearAutoDetectedAttributeTypes(): void
    {
        self::$autoDetectedAttributeTypes = [];
        self::$autoDetectedNullableAttributes = [];
        self::$autoDetectedDecimalScales = [];
    }
}
