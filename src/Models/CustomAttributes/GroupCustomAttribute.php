<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Override;
use Stringable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\NotSupportedException;

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
     */
    public function validateGroup(Model $owner, string $attribute): void
    {
        if (!$owner instanceof CustomAttributeInterface) {
            return;
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
                $owner->addError($attribute, Lang::t('skeleton', 'CUSTOM_ATTRIBUTE_ERROR_MIN_COUNT', [
                    'attribute' => $owner->getAttributeLabel($attribute),
                    'min' => $this->minCount,
                ]));
            }

            if ($this->maxCount !== null && $count > $this->maxCount) {
                $owner->addError($attribute, Lang::t('skeleton', 'CUSTOM_ATTRIBUTE_ERROR_MAX_COUNT', [
                    'attribute' => $owner->getAttributeLabel($attribute),
                    'max' => $this->maxCount,
                ]));
            }
        }

        $owner->{$attribute} = $this->multiple ? ($values ?: null) : (($values[0] ?? []) ?: null);
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

    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|null
    {
        return $value === null ? null : parent::formatValue($owner, $value);
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        throw new NotSupportedException('Group custom attributes cannot be rendered yet.');
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
