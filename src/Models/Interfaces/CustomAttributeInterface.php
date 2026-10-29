<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\RecordCustomAttribute;

interface CustomAttributeInterface
{
    /**
     * Runs for every loaded record, so it must be cheap and free of side effects: a definition depending on a
     * relation eager loads it or guards with `isRelationPopulated()`.
     *
     * @return list<CustomAttribute>
     */
    public function getCustomAttributes(): array;

    /**
     * @param list<CustomAttribute>|\Closure(static): list<CustomAttribute>|null $customAttributes
     */
    public function setCustomAttributes(array|\Closure|null $customAttributes): void;

    /**
     * @return array<string, CustomAttribute>
     */
    public function getCustomAttributeDefinitions(): array;

    /**
     * `$name` may be a translated variant.
     */
    public function getCustomAttribute(string $name): ?CustomAttribute;

    /**
     * Includes the translated variants.
     *
     * @return list<string>
     */
    public function getCustomAttributeNames(): array;

    /**
     * @return list<string>
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
     * `null` values are omitted.
     *
     * @return array<string, mixed>
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
