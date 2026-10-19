<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Override;
use Stringable;
use yii\base\Model;

/**
 * The record a definition resolves to lives on the owner ({@see CustomAttributeInterface::getCustomAttributeRecord()}),
 * since a definition is shared by every record of the type.
 */
abstract class RecordCustomAttribute extends CustomAttribute
{
    /**
     * Unscoped: a record that left the scope since is still the one the editor picked.
     *
     * @param list<int> $ids
     * @return array<int, ActiveRecord>
     */
    abstract public function findRecords(Model $owner, array $ids): array;

    abstract protected function getRecordLabel(ActiveRecord $record): string;

    #[Override]
    public function normalize(mixed $value): ?int
    {
        return is_numeric($value) && (int)$value > 0 ? (int)$value : null;
    }

    #[Override]
    public function unserialize(mixed $value): ?int
    {
        return $this->normalize($value);
    }

    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        $id = $this->normalize($value);

        if ($id === null) {
            return null;
        }

        $record = $this->findRecords($owner, [$id])[$id] ?? null;

        return $record ? $this->getRecordLabel($record) : "#$id";
    }

    /**
     * Whether the value is the one the owner started from, which a changed-value rule exempts. Inside a group, where
     * no item has an old value, any id in the baseline of the top-level group counts, so a reorder exempts it too.
     */
    public function isBaselineValue(Model $owner, string $name, mixed $value): bool
    {
        $value = $this->normalize($value);

        if ($value === null) {
            return true;
        }

        if (!$owner instanceof CustomAttributeGroupItem) {
            return $owner instanceof CustomAttributeInterface
                && $this->normalize($owner->getCustomAttributeBaseline($name)) === $value;
        }

        $group = $owner->getGroup();
        $record = $owner->getOwner();

        while ($record instanceof CustomAttributeGroupItem) {
            $group = $record->getGroup();
            $record = $record->getOwner();
        }

        if (!$record instanceof CustomAttributeInterface) {
            return false;
        }

        $baseline = $record->getCustomAttributeBaseline($group->name);

        return in_array($value, $this->flattenIds($baseline), true);
    }

    /**
     * @return list<int>
     */
    private function flattenIds(mixed $value): array
    {
        if (!is_array($value)) {
            $id = $this->normalize($value);
            return $id === null ? [] : [$id];
        }

        $ids = [];

        foreach ($value as $item) {
            $ids = [...$ids, ...$this->flattenIds($item)];
        }

        return $ids;
    }
}
