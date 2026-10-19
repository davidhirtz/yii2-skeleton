<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\RecordCustomAttribute;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;

/**
 * Implemented via {@see CustomAttributesTrait}.
 */
interface CustomAttributeInterface
{
    /**
     * Must be cheap and free of side effects, it runs for every loaded record. A definition depending on a relation has
     * to eager load it or guard with `isRelationPopulated()`.
     *
     * @return list<CustomAttribute> the definitions for the model's current state
     */
    public function getCustomAttributes(): array;

    /**
     * @param list<CustomAttribute>|\Closure(static): list<CustomAttribute>|null $customAttributes replaces those of
     * the type options; settable through the container like `i18nAttributes`
     */
    public function setCustomAttributes(array|\Closure|null $customAttributes): void;

    /**
     * @return array<string, CustomAttribute>
     */
    public function getCustomAttributeDefinitions(): array;

    /**
     * @param string $name the definition name or one of its translated variants
     */
    public function getCustomAttribute(string $name): ?CustomAttribute;

    /**
     * @return list<string> the definition names plus their translated variants
     */
    public function getCustomAttributeNames(): array;

    /**
     * @return list<string> the translatable definition names, untranslated
     */
    public function getTranslatableCustomAttributeNames(): array;

    /**
     * @return list<array>
     */
    public function getCustomAttributeRules(): array;

    /**
     * @return array<string, string>
     */
    public function getCustomAttributeLabels(): array;

    /**
     * @return array<string, string|null>
     */
    public function getCustomAttributeHints(): array;

    /**
     * @return array<int|string, CustomAttributeGroupItem>
     */
    public function getCustomAttributeItems(string $name): array;

    /**
     * @return array<string, mixed> the JSON representation of every defined value, `null` values omitted
     */
    public function getSerializedCustomAttributes(): array;

    public function validateCustomAttributeGroup(string $attribute): void;

    public function validateCustomAttributeUpload(string $attribute): void;

    public function applyCustomAttributeDefaults(): void;

    public function resetCustomAttributes(): void;

    public function populateCustomAttributeRecord(string $name, ?ActiveRecord $record): void;

    public function getCustomAttributeRecord(string $name): ?ActiveRecord;

    /**
     * @param class-string<RecordCustomAttribute> $class
     * @return array<int, array<string, int>>
     */
    public function getCustomAttributeRecordIds(string $class): array;

    /**
     * @param class-string<RecordCustomAttribute> $class
     */
    public function clearCustomAttributeRecordId(string $class, int $id): bool;

    public function getCustomAttributeBaseline(string $name): mixed;

    /**
     * @param array<string, mixed> $baselines
     */
    public function setCustomAttributeBaselines(array $baselines): void;
}
