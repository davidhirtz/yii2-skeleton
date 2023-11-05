<?php

namespace davidhirtz\yii2\skeleton\models\actions;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Exception;
use Yii;

class ReorderActiveRecordsAction
{
    /**
     * @see static::getTotalRowsUpdated()
     */
    private int $_totalRowsUpdated = 0;

    /**
     * @param ActiveRecord[] $models
     */
    public function __construct(
        protected array $models,
        protected array $order = [],
        protected string $attribute = 'position',
        protected ?string $index = null
    ) {
        if (!$this->beforeReorder()) {
            return;
        }

        if ($this->reorderActiveRecords()) {
            $this->afterReorder();
        }
    }

    protected function reorderActiveRecords(): int
    {
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            foreach ($this->models as $model) {
                $primaryKey = $model->getPrimaryKey(true);
                $position = $this->getNewPosition($primaryKey);

                if ($position != $model->getAttribute($this->attribute)) {
                    $this->_totalRowsUpdated += $model::updateAll([$this->attribute => $position], $primaryKey);
                }
            }

            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return $this->_totalRowsUpdated;
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
}