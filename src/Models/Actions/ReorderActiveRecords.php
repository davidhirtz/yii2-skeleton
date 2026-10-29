<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Web\Request;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @template TActiveRecord of ActiveRecordInterface
 * @property TActiveRecord[] $models
 */
class ReorderActiveRecords
{
    private int $totalRowsUpdated = 0;

    /**
     * @var array<int|string, int>|null
     */
    private ?array $positions = null;

    /**
     * @param TActiveRecord[] $models
     * @param array<int|string, int> $order
     */
    public function __construct(
        protected array $models,
        protected array $order = [],
        protected string $attribute = 'position',
        protected ?string $index = null
    ) {
    }

    /**
     * {@see static::afterReorder()} writes the trail and touches the parent, so it shares the positions' transaction.
     */
    public function run(): int|false
    {
        $result = Yii::$app->getDb()->transaction(function (): int|false {
            if (!$this->beforeReorder()) {
                return false;
            }

            $this->totalRowsUpdated = $this->reorderActiveRecords();

            if ($this->totalRowsUpdated) {
                $this->afterReorder();
            }

            return $this->totalRowsUpdated;
        });

        if ($result) {
            $this->afterCommit();
        }

        return $result;
    }

    protected function reorderActiveRecords(): int
    {
        $totalRowsUpdated = 0;

        foreach ($this->models as $model) {
            $primaryKey = $model->getPrimaryKey(true);
            $position = $this->getNewPosition($primaryKey);

            if ($position !== $model->getAttribute($this->attribute)) {
                $totalRowsUpdated += $model::updateAll([$this->attribute => $position], $primaryKey);
            }
        }

        return $totalRowsUpdated;
    }

    protected function beforeReorder(): bool
    {
        return true;
    }

    protected function afterReorder(): void
    {
    }

    /**
     * A cache cleared before the commit can be refilled with the old order by a request in between.
     */
    protected function afterCommit(): void
    {
    }

    /**
     * @param array<string, mixed> $primaryKey
     */
    protected function getNewPosition(array $primaryKey): int
    {
        $this->positions ??= $this->getNewPositions();
        return $this->positions[$this->getIndex($primaryKey)] ?? 1;
    }

    /**
     * A filtered grid posts some of the rows, which share out the positions they held while the hidden rows keep
     * theirs; positions that do not tell the rows apart are renumbered from `1`.
     *
     * @return array<int|string, int>
     */
    private function getNewPositions(): array
    {
        $indexes = [];
        $positions = [];

        foreach ($this->models as $model) {
            $index = $this->getIndex($model->getPrimaryKey(true));
            $indexes[$index] = $this->order[$index] ?? 0;
            $positions[] = (int)$model->getAttribute($this->attribute);
        }

        sort($positions);

        if (count(array_unique($positions)) !== count($positions) || in_array(0, $positions, true)) {
            $positions = $positions ? range(1, count($positions)) : [];
        }

        asort($indexes);

        return array_combine(array_keys($indexes), $positions);
    }

    /**
     * @param array<string, mixed> $primaryKey
     */
    private function getIndex(array $primaryKey): int|string
    {
        return $this->index ? $primaryKey[$this->index] : current($primaryKey);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function runWithBodyParam(string $paramName, array $config = []): int|false
    {
        $order = array_map(intval(...), array_filter(Request::current()?->getBodyParam($paramName, []) ?? []));

        if ($order) {
            $action = Yii::createObject(static::class, [...array_values($config), $order]);
            return $action->run();
        }

        return 0;
    }
}
