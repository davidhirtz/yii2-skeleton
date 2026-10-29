<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Setup;

use Hirtz\Skeleton\Db\ActiveRecord;
use yii\base\BaseObject;

/**
 * A record that cannot be inserted is kept for the report, so one invalid declaration does not cost the rest.
 */
abstract class Seeder extends BaseObject
{
    /**
     * @var list<string> the keys whose seeders run first; a key no seeder reads is ignored
     */
    public array $dependencies = [];

    /**
     * @var list<ActiveRecord>
     */
    private array $inserted = [];

    /**
     * @var list<ActiveRecord>
     */
    private array $failed = [];

    /**
     * @param array<array-key, mixed>|null $value `null` when the setup file does not name the key
     */
    abstract public function run(?array $value): void;

    /**
     * @return list<ActiveRecord>
     */
    public function getInserted(): array
    {
        return $this->inserted;
    }

    /**
     * @return list<ActiveRecord>
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    protected function insert(ActiveRecord $record): bool
    {
        if ($record->insert()) {
            $this->inserted[] = $record;
            return true;
        }

        $this->failed[] = $record;
        return false;
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    protected static function getDeclaredType(array $attributes): ?int
    {
        return isset($attributes['type']) && is_numeric($attributes['type']) ? (int)$attributes['type'] : null;
    }
}
