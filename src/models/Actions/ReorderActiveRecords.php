<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Helpers\ArrayHelper;
use Exception;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @template TActiveRecord of ActiveRecordInterface
 * @property TActiveRecord[] $models
 */
class ReorderActiveRecords
{
    /**
     * @see static::getTotalRowsUpdated()
     */
    private int $_totalRowsUpdated = 0;

    /**
     * @param TActiveRecord[] $models
     */
    public function __construct(
        protected array $models,
        protected array $order = [],
        protected string $attribute = 'position',
        protected ?string $index = null
    ) {
    }

    public function run(): int|false
    {
        if (!$this->beforeReorder()) {
            return false;
        }

        if ($this->reorderActiveRecords()) {
            $this->afterReorder();
        }

        return $this->_totalRowsUpdated;
    }

    protected function reorderActiveRecords(): int
    {
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            $this->reorderActiveRecordsInternal();
            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return $this->_totalRowsUpdated;
    }

    protected function reorderActiveRecordsInternal(): void
    {
        $this->_totalRowsUpdated = 0;

        foreach ($this->models as $model) {
            $primaryKey = $model->getPrimaryKey(true);
            $position = $this->getNewPosition($primaryKey);

            if ($position !== $model->getAttribute($this->attribute)) {
                $this->_totalRowsUpdated += $model::updateAll([$this->attribute => $position], $primaryKey);
            }
        }
    }

    protected function beforeReorder(): bool
    {
        return true;
    }

    protected function afterReorder(): void
    {
    }

    protected function getNewPosition(array $primaryKey): int
    {
        $index = $this->index ? $primaryKey[$this->index] : current($primaryKey);
        return ArrayHelper::getValue($this->order, $index, 0) + 1;
    }

    public function getTotalRowsUpdated(): int
    {
        return $this->_totalRowsUpdated;
    }

    public static function runWithBodyParam(string $paramName, array $config = []): int|false
    {
        $order = array_map(intval(...), array_filter(Yii::$app->getRequest()->getBodyParam($paramName, [])));

        if ($order) {
            $action = Yii::createObject(static::class, [...array_values($config), $order]);
            return $action->run();
        }

        return 0;
    }
}
