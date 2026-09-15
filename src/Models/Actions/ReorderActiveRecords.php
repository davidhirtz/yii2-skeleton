<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Helpers\ArrayHelper;
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
     * {@see static::afterReorder()} writes the trail and touches the parent record, so it belongs to the same
     * transaction as the positions it describes.
     */
    public function run(): int|false
    {
        return Yii::$app->getDb()->transaction(function (): int|false {
            if (!$this->beforeReorder()) {
                return false;
            }

            $this->totalRowsUpdated = $this->reorderActiveRecords();

            if ($this->totalRowsUpdated) {
                $this->afterReorder();
            }

            return $this->totalRowsUpdated;
        });
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
     * @param array<string, mixed> $primaryKey
     */
    protected function getNewPosition(array $primaryKey): int
    {
        $index = $this->index ? $primaryKey[$this->index] : current($primaryKey);
        return ArrayHelper::getValue($this->order, $index, 0) + 1;
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
