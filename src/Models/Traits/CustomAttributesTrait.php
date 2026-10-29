<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Closure;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\RecordCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Override;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * Requires {@see I18nAttributesTrait} for translatable definitions.
 *
 * @mixin Model
 */
trait CustomAttributesTrait
{
    private const string CUSTOM_ATTRIBUTE_NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /**
     * @var array<string, CustomAttribute>|null
     */
    private ?array $customAttributeDefinitions = null;

    private ?string $customAttributesKey = null;

    /**
     * @var list<CustomAttribute>|Closure(static): list<CustomAttribute>|null
     */
    private array|Closure|null $customAttributes = null;

    /**
     * @var array<string, array<int|string, CustomAttributeGroupItem>>
     */
    private array $customAttributeItems = [];

    /**
     * @var array<string, mixed>
     */
    private array $customAttributeItemSources = [];

    /**
     * @var array<string, array{int, ActiveRecord|null}>
     */
    private array $customAttributeRecords = [];

    /**
     * @var array<string, mixed>
     */
    private array $customAttributeBaselines = [];

    /**
     * @return list<CustomAttribute>
     */
    public function getCustomAttributes(): array
    {
        $attributes = $this->customAttributes;

        if ($attributes === null) {
            return $this instanceof TypeAttributeInterface
                ? $this->getType()?->getCustomAttributes($this) ?? []
                : [];
        }

        return $attributes instanceof Closure ? $attributes($this) : $attributes;
    }

    /**
     * @param list<CustomAttribute>|Closure(static): list<CustomAttribute>|null $customAttributes
     */
    public function setCustomAttributes(array|Closure|null $customAttributes): void
    {
        $this->customAttributes = $customAttributes;
        $this->resetCustomAttributes();
    }

    /**
     * @return array<string, CustomAttribute>
     */
    public function getCustomAttributeDefinitions(): array
    {
        $key = json_encode($this->getCustomAttributesKey(), JSON_THROW_ON_ERROR);

        if ($this->customAttributeDefinitions !== null) {
            if ($this->customAttributesKey === $key) {
                return $this->customAttributeDefinitions;
            }

            $this->customAttributeItems = [];
            $this->customAttributeItemSources = [];
            $this->customAttributeRecords = [];
            $this->resetValidators();
        }

        // Primed before resolving: `getCustomAttributes()` reads an attribute whose getter can run through
        // `attributes()`, which asks for the definitions again.
        $this->customAttributesKey = $key;
        $this->customAttributeDefinitions = [];
        $this->customAttributeDefinitions = $this->createCustomAttributeDefinitions();

        return $this->customAttributeDefinitions;
    }

    public function getCustomAttribute(string $name): ?CustomAttribute
    {
        $definitions = $this->getCustomAttributeDefinitions();

        if (isset($definitions[$name])) {
            return $definitions[$name];
        }

        foreach ($definitions as $definition) {
            if (in_array($name, $definition->getAttributeNames($this), true)) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function getCustomAttributeNames(): array
    {
        $names = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            $names = [...$names, ...$definition->getAttributeNames($this)];
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public function getTranslatableCustomAttributeNames(): array
    {
        $names = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            if ($definition->isTranslatable()) {
                $names[] = $definition->name;
            }
        }

        return $names;
    }

    /**
     * @return list<array>
     */
    public function getCustomAttributeRules(): array
    {
        $rules = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            $rules = [...$rules, ...$definition->getRules($this)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function getCustomAttributeLabels(): array
    {
        $labels = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            $labels[$definition->name] = $definition->getLabel();
        }

        return $labels;
    }

    /**
     * @return array<string, string|null>
     */
    public function getCustomAttributeHints(): array
    {
        $hints = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            $hints[$definition->name] = $definition->getHint();
        }

        return $hints;
    }

    /**
     * Rebuilt only when the attribute was assigned since, so the form renders the instances carrying the errors.
     *
     * @return array<int|string, CustomAttributeGroupItem>
     */
    public function getCustomAttributeItems(string $name): array
    {
        $value = $this->{$name};

        if (!array_key_exists($name, $this->customAttributeItems) || $this->customAttributeItemSources[$name] !== $value) {
            $definition = $this->getCustomAttributeDefinitions()[$name] ?? null;

            $this->customAttributeItems[$name] = $definition instanceof GroupCustomAttribute
                ? $definition->createItems($this, $value)
                : [];

            $this->customAttributeItemSources[$name] = $value;
        }

        return $this->customAttributeItems[$name];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSerializedCustomAttributes(): array
    {
        $values = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this) as $name) {
                $value = $definition->serialize($this->{$name});

                if ($value !== null) {
                    $values[$name] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * @see GroupCustomAttribute::getValidationRules()
     */
    public function validateCustomAttributeGroup(string $attribute): void
    {
        $definition = $this->getCustomAttribute($attribute);

        if ($definition instanceof GroupCustomAttribute) {
            // The normalized value is recorded as the items' source, so the validated instances survive the write-back.
            $value = $definition->validateGroup($this, $attribute);

            $this->{$attribute} = $value;
            $this->customAttributeItemSources[$attribute] = $value;
        }
    }

    /**
     * @see UploadCustomAttribute::getValidationRules()
     */
    public function validateCustomAttributeUpload(string $attribute): void
    {
        $definition = $this->getCustomAttribute($attribute);

        if ($definition instanceof UploadCustomAttribute) {
            $definition->validateUpload($this, $attribute);
        }
    }

    public function applyCustomAttributeDefaults(): void
    {
        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            $default = $definition->getDefault();

            if ($default === null) {
                continue;
            }

            foreach ($definition->getAttributeNames($this) as $name) {
                $this->{$name} ??= $default;
            }
        }
    }

    public function resetCustomAttributes(): void
    {
        $this->customAttributeDefinitions = null;
        $this->customAttributesKey = null;
        $this->customAttributeItems = [];
        $this->customAttributeItemSources = [];
        $this->customAttributeRecords = [];

        $this->resetValidators();
    }

    /**
     * `$name` may be a translated variant.
     */
    public function populateCustomAttributeRecord(string $name, ?ActiveRecord $record): void
    {
        $id = $this->getCustomAttributeRecordId($name);

        if ($id !== null) {
            $this->customAttributeRecords[$name] = [$id, $record];
        }
    }

    /**
     * Resolved again once the value was reassigned.
     */
    public function getCustomAttributeRecord(string $name): ?ActiveRecord
    {
        $id = $this->getCustomAttributeRecordId($name);

        if ($id === null) {
            return null;
        }

        [$resolvedId, $record] = $this->customAttributeRecords[$name] ?? [null, null];

        if ($resolvedId !== $id) {
            $definition = $this->getCustomAttribute($name);

            $record = $definition instanceof RecordCustomAttribute
                ? $definition->findRecords($this, [$id])[$id] ?? null
                : null;

            $this->customAttributeRecords[$name] = [$id, $record];
        }

        return $record;
    }

    /**
     * Nested names are joined by `.`.
     *
     * @param class-string<RecordCustomAttribute> $class
     * @return array<int, array<string, int>>
     */
    public function getCustomAttributeRecordIds(string $class): array
    {
        $ids = [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this) as $name) {
                if ($definition instanceof $class) {
                    $id = $definition->normalize($this->{$name});

                    if ($id !== null) {
                        $ids[$id][$name] = ($ids[$id][$name] ?? 0) + 1;
                    }
                } elseif ($definition instanceof GroupCustomAttribute) {
                    foreach ($this->getCustomAttributeItems($name) as $item) {
                        foreach ($item->getCustomAttributeRecordIds($class) as $id => $counts) {
                            foreach ($counts as $itemName => $count) {
                                $ids[$id]["$name.$itemName"] = ($ids[$id]["$name.$itemName"] ?? 0) + $count;
                            }
                        }
                    }
                }
            }
        }

        foreach ($ids as &$counts) {
            ksort($counts);
        }

        return $ids;
    }

    /**
     * In a group the value alone is cleared, not its row.
     *
     * @param class-string<RecordCustomAttribute> $class
     */
    public function clearCustomAttributeRecordId(string $class, int $id): bool
    {
        $changed = false;

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this) as $name) {
                if ($definition instanceof $class) {
                    if ($definition->normalize($this->{$name}) === $id) {
                        $this->{$name} = null;
                        $changed = true;
                    }
                } elseif ($definition instanceof GroupCustomAttribute) {
                    $items = $this->getCustomAttributeItems($name);
                    $cleared = false;

                    foreach ($items as $item) {
                        $cleared = $item->clearCustomAttributeRecordId($class, $id) || $cleared;
                    }

                    if ($cleared) {
                        $values = array_values(array_map(
                            static fn (CustomAttributeGroupItem $item): array => $item->getSerializedCustomAttributes(),
                            $items,
                        ));

                        $value = $definition->isMultiple() ? $values : ($values[0] ?: null);

                        $this->{$name} = $value;
                        $this->customAttributeItemSources[$name] = $value;
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * The value a changed-value rule compares against: the stored one, or what a duplicate was handed by its source.
     */
    public function getCustomAttributeBaseline(string $name): mixed
    {
        if (array_key_exists($name, $this->customAttributeBaselines)) {
            return $this->customAttributeBaselines[$name];
        }

        return $this instanceof ActiveRecord && !$this->getIsNewRecord()
            ? $this->getOldAttribute($name)
            : null;
    }

    /**
     * @param array<string, mixed> $baselines
     */
    public function setCustomAttributeBaselines(array $baselines): void
    {
        $this->customAttributeBaselines = $baselines;
    }

    /**
     * The custom attributes are assigned last, since a value of the first pass can change which definitions apply.
     *
     * @param array<string, mixed> $values
     */
    #[Override]
    public function setAttributes($values, $safeOnly = true): void
    {
        $values = (array)$values;

        parent::setAttributes(array_diff_key($values, array_flip($this->getCustomAttributeNames())), $safeOnly);
        parent::setAttributes(array_intersect_key($values, array_flip($this->getCustomAttributeNames())), $safeOnly);
    }

    private function getCustomAttributeRecordId(string $name): ?int
    {
        $definition = $this->getCustomAttribute($name);

        return $definition instanceof RecordCustomAttribute
            ? $definition->normalize($this->{$name})
            : null;
    }

    protected function getCustomAttributesKey(): mixed
    {
        return $this instanceof TypeAttributeInterface && $this instanceof ActiveRecord
            ? $this->getAttribute('type')
            : null;
    }

    /**
     * @return array<string, CustomAttribute>
     */
    private function createCustomAttributeDefinitions(): array
    {
        $columns = $this instanceof ActiveRecord ? array_keys($this::getTableSchema()->columns) : [];
        $definitions = [];
        $customAttributes = $this->getCustomAttributes();

        // Without the column, a loaded record would keep its values in memory only and drop them on save.
        if ($customAttributes && $this instanceof ActiveRecord && !in_array($this->getCustomAttributesColumn(), $columns, true)) {
            throw new InvalidConfigException(static::class . ' declares custom attributes but ' . $this::tableName() . ' has no "' . $this->getCustomAttributesColumn() . '" column.');
        }

        // The custom part of `getI18nAttributes()` is primed empty above, so this is the model's own list.
        $i18nAttributes = $this instanceof I18nAttributeInterface
            ? $this->getI18nAttributesNames($this->getI18nAttributes())
            : [];

        foreach ($customAttributes as $definition) {
            $name = $definition->name;

            if (!preg_match(self::CUSTOM_ATTRIBUTE_NAME_PATTERN, $name)) {
                throw new InvalidConfigException("Custom attribute \"$name\" is not a valid attribute name.");
            }

            if (isset($definitions[$name])) {
                throw new InvalidConfigException("Custom attribute \"$name\" is defined twice.");
            }

            if (in_array($name, $columns, true)) {
                throw new InvalidConfigException("Custom attribute \"$name\" collides with a column of the same name.");
            }

            if (in_array($name, $i18nAttributes, true)) {
                throw new InvalidConfigException("Custom attribute \"$name\" collides with a translated attribute.");
            }

            $definitions[$name] = $definition;
        }

        return $definitions;
    }
}
