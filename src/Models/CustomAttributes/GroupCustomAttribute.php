<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\GroupField;
use Override;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;

class GroupCustomAttribute extends CustomAttribute
{
    /**
     * @var list<CustomAttribute>
     */
    protected array $attributes = [];
    protected bool $multiple = false;
    protected int $minCount = 0;
    protected ?int $maxCount = null;
    protected bool $sortable = true;

    /**
     * @var class-string<CustomAttributeGroupItem>
     */
    protected string $itemClass = CustomAttributeGroupItem::class;

    /**
     * @param list<CustomAttribute> $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function minCount(int $minCount): static
    {
        $this->minCount = $minCount;
        return $this;
    }

    public function maxCount(?int $maxCount): static
    {
        $this->maxCount = $maxCount;
        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * @param class-string<CustomAttributeGroupItem> $itemClass
     */
    public function itemClass(string $itemClass): static
    {
        $this->itemClass = $itemClass;
        return $this;
    }

    /**
     * @return list<CustomAttribute>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function getMinCount(): int
    {
        return $this->minCount;
    }

    public function getMaxCount(): ?int
    {
        return $this->maxCount;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    #[Override]
    public function translatable(bool $translatable = true): static
    {
        throw new InvalidConfigException('A group cannot be translated, only the attributes it contains can.');
    }

    /**
     * Named rather than a closure: `InlineValidator` rebinds a closure to the model, which fails for one created in
     * the scope of the definition.
     */
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [['validateCustomAttributeGroup']];
    }

    /**
     * Drops rows whose every value is empty, reindexes the rest in request order and copies the item errors into the
     * owner as `links.0.url`, so the save is blocked and the form shows them next to their input.
     *
     * @return array<string, mixed>|list<array<string, mixed>>|null the normalized value the owner assigns
     */
    public function validateGroup(Model $owner, string $attribute): mixed
    {
        if (!$owner instanceof CustomAttributeInterface) {
            return $owner->{$attribute};
        }

        $items = $owner->getCustomAttributeItems($attribute);
        $values = [];

        foreach ($items as $index => $item) {
            if (!$item->validate()) {
                foreach ($item->getErrors() as $name => $errors) {
                    foreach ($errors as $error) {
                        $owner->addError("$attribute.$index.$name", $error);
                    }
                }
            }

            $values[] = $item->getSerializedCustomAttributes();
        }

        if ($this->multiple) {
            $count = count($values);

            if ($count < $this->minCount) {
                $owner->addError($attribute, Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ERROR_MIN_COUNT', [
                    'attribute' => $owner->getAttributeLabel($attribute),
                    'min' => $this->minCount,
                ]));
            }

            if ($this->maxCount !== null && $count > $this->maxCount) {
                $owner->addError($attribute, Yii::t('skeleton', 'CUSTOM_ATTRIBUTE_ERROR_MAX_COUNT', [
                    'attribute' => $owner->getAttributeLabel($attribute),
                    'max' => $this->maxCount,
                ]));
            }
        }

        return $this->multiple ? ($values ?: null) : (($values[0] ?? []) ?: null);
    }

    /**
     * @return array<int|string, CustomAttributeGroupItem>
     */
    public function createItems(Model $owner, mixed $value): array
    {
        $rows = $this->multiple
            ? array_filter(is_array($value) ? $value : [], $this->hasValues(...))
            : [is_array($value) ? $value : []];

        $items = [];

        foreach (array_values($rows) as $index => $row) {
            $items[$index] = $this->createItem($owner, (string)$index, $row);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function createItem(Model $owner, string $index, array $values = []): CustomAttributeGroupItem
    {
        $formName = $this->multiple
            ? "{$owner->formName()}[$this->name][$index]"
            : "{$owner->formName()}[$this->name]";

        /** @var CustomAttributeGroupItem $item */
        $item = Yii::createObject($this->itemClass, [$this, $owner, $formName]);
        $item->setAttributes($values, false);

        return $item;
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        return $value;
    }

    /**
     * A label per child of every row, so the trail can pair the old and the new value of each one. A nested group is
     * flattened into the same map rather than nested, so its children diff on their own too.
     *
     * @return array<string, string|Stringable|null>
     */
    #[Override]
    public function formatValue(Model $owner, mixed $value): array
    {
        return $this->getFormattedValues($owner, $value, '');
    }

    /**
     * @return array<string, string|Stringable|null>
     */
    private function getFormattedValues(Model $owner, mixed $value, string $prefix): array
    {
        $rows = $this->multiple ? $value : [$value];

        if (!is_array($rows)) {
            return [];
        }

        $item = $this->createItem($owner, '0');
        $values = [];
        $index = 0;

        foreach ($rows as $row) {
            if (!is_array($row) || !$this->hasValues($row)) {
                continue;
            }

            $rowPrefix = $this->multiple ? $prefix . ++$index . '. ' : $prefix;

            foreach ($this->getFormattedNames($item, [$row]) as $name) {
                $definition = $item->getCustomAttribute($name);
                $label = $rowPrefix . $item->getAttributeLabel($name);

                $values = $definition instanceof self
                    ? [...$values, ...$definition->getFormattedValues($item, $row[$name] ?? null, "$label ")]
                    : [...$values, $label => $definition?->formatValue($item, $row[$name] ?? null)];
            }
        }

        return $values;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(GroupField::make()->group($this), $owner);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [
            array_map(static fn (CustomAttribute $attribute): string => $attribute->getFingerprint(), $this->attributes),
            $this->multiple,
            $this->minCount,
            $this->maxCount,
            $this->sortable,
            $this->itemClass,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    private function getFormattedNames(CustomAttributeGroupItem $item, array $rows): array
    {
        $names = [];

        foreach ($item->getCustomAttributeDefinitions() as $definition) {
            $names = [...$names, ...$definition->getAttributeNames($item)];
        }

        foreach ($rows as $row) {
            $names = [...$names, ...array_keys($row)];
        }

        $filled = [];

        foreach (array_unique($names) as $name) {
            foreach ($rows as $row) {
                if ($this->hasValues($row[$name] ?? null)) {
                    $filled[] = $name;
                    break;
                }
            }
        }

        return $filled;
    }

    private function hasValues(mixed $row): bool
    {
        if (!is_array($row)) {
            return $row !== null && $row !== '';
        }

        foreach ($row as $value) {
            if ($this->hasValues($value)) {
                return true;
            }
        }

        return false;
    }
}
