<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Closure;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Override;
use yii\base\InvalidConfigException;

/**
 * Stores typed attribute definitions in one JSON column instead of a column each. Requires {@see I18nAttributesTrait}
 * for translatable definitions.
 *
 * @mixin ActiveRecord
 */
trait CustomAttributesTrait
{
    private const string CUSTOM_ATTRIBUTE_NAME_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /**
     * @var array<string, CustomAttribute>|null
     */
    private ?array $_customAttributeDefinitions = null;

    private ?string $_customAttributesKey = null;

    /**
     * @var list<CustomAttribute>|Closure(static): list<CustomAttribute>|null
     */
    private array|Closure|null $_customAttributes = null;

    /**
     * @var array<string, array<int|string, CustomAttributeGroupItem>>
     */
    private array $_customAttributeItems = [];

    /**
     * @var array<string, mixed> the value each item list was built from
     */
    private array $_customAttributeItemSources = [];

    /**
     * @return list<CustomAttribute> the configured definitions, or those of the type options when none were set
     */
    public function getCustomAttributes(): array
    {
        $attributes = $this->_customAttributes;

        if ($attributes === null && $this instanceof TypeAttributeInterface) {
            $attributes = $this->getTypeOptions()['customAttributes'] ?? [];
        }

        return $attributes instanceof Closure ? $attributes($this) : $attributes ?? [];
    }

    /**
     * @param list<CustomAttribute>|Closure(static): list<CustomAttribute>|null $customAttributes
     */
    public function setCustomAttributes(array|Closure|null $customAttributes): void
    {
        $this->_customAttributes = $customAttributes;
        $this->resetCustomAttributes();
    }

    /**
     * @return array<string, CustomAttribute>
     */
    public function getCustomAttributeDefinitions(): array
    {
        $key = json_encode($this->getCustomAttributesKey());

        if ($this->_customAttributeDefinitions !== null) {
            if ($this->_customAttributesKey === $key) {
                return $this->_customAttributeDefinitions;
            }

            $this->_customAttributeItems = [];
            $this->_customAttributeItemSources = [];
            $this->resetValidators();
        }

        // Primed before resolving: `getCustomAttributes()` reads an attribute whose getter can run through
        // `attributes()`, which asks for the definitions again.
        $this->_customAttributesKey = $key;
        $this->_customAttributeDefinitions = [];
        $this->_customAttributeDefinitions = $this->createCustomAttributeDefinitions();

        return $this->_customAttributeDefinitions;
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
     * Rebuilt when the attribute was assigned since, and kept otherwise, so the form renders the instances that carry
     * the validation errors.
     *
     * @return array<int|string, CustomAttributeGroupItem>
     */
    public function getCustomAttributeItems(string $name): array
    {
        $value = $this->{$name};

        if (!array_key_exists($name, $this->_customAttributeItems) || $this->_customAttributeItemSources[$name] !== $value) {
            $definition = $this->getCustomAttributeDefinitions()[$name] ?? null;

            $this->_customAttributeItems[$name] = $definition instanceof GroupCustomAttribute
                ? $definition->createItems($this, $value)
                : [];

            $this->_customAttributeItemSources[$name] = $value;
        }

        return $this->_customAttributeItems[$name];
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
     * @uses GroupCustomAttribute::getValidationRules()
     */
    public function validateCustomAttributeGroup(string $attribute): void
    {
        $definition = $this->getCustomAttribute($attribute);

        if ($definition instanceof GroupCustomAttribute) {
            // The normalized value is recorded as the items' source, so the validated instances survive the write-back.
            $value = $definition->validateGroup($this, $attribute);

            $this->{$attribute} = $value;
            $this->_customAttributeItemSources[$attribute] = $value;
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
        $this->_customAttributeDefinitions = null;
        $this->_customAttributesKey = null;
        $this->_customAttributeItems = [];
        $this->_customAttributeItemSources = [];

        $this->resetValidators();
    }

    /**
     * The custom attributes are assigned last, in a pass of their own: a value of the first pass can change which
     * definitions apply, which both drops the new ones from `safeAttributes()` and makes assigning a name the new
     * state does not have throw.
     */
    #[Override]
    public function setAttributes($values, $safeOnly = true): void
    {
        $values = (array)$values;

        parent::setAttributes(array_diff_key($values, array_flip($this->getCustomAttributeNames())), $safeOnly);
        parent::setAttributes(array_intersect_key($values, array_flip($this->getCustomAttributeNames())), $safeOnly);
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
        $columns = $this instanceof ActiveRecord ? array_keys(static::getTableSchema()->columns) : [];
        $definitions = [];
        $customAttributes = $this->getCustomAttributes();

        // Without the column, a loaded record would keep its values in memory only and drop them on save.
        if ($customAttributes && $this instanceof ActiveRecord && !in_array($this->getCustomAttributesColumn(), $columns, true)) {
            throw new InvalidConfigException(static::class . ' declares custom attributes but ' . static::tableName() . ' has no "' . $this->getCustomAttributesColumn() . '" column.');
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
