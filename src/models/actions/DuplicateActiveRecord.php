<?php

namespace davidhirtz\yii2\skeleton\models\actions;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\events\DuplicateActiveRecordEvent;
use Exception;
use Yii;

/**
 * @template T
 * @property T $model
 * @property T $duplicate
 */
class DuplicateActiveRecord
{
    public const EVENT_AFTER_CLONE = 'afterClone';
    public const EVENT_BEFORE_CLONE = 'beforeClone';

    public ActiveRecord $duplicate;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $this->duplicate = $this->model::create();
        $this->duplicate->setAttributes([...$this->getSafeAttributes(), ...$attributes], false);
    }

    public function duplicate(): ?ActiveRecord
    {
        if ($this->model::getDb()->getTransaction()) {
            return $this->duplicateInternal();
        }

        $transaction = $this->model::getDb()->beginTransaction();

        try {
            $duplicate = $this->duplicateInternal();
            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return $duplicate;
    }

    protected function duplicateInternal(): ?ActiveRecord
    {
        if (!$this->beforeDuplicate()) {
            return null;
        }

        if ($this->duplicate->insert()) {
            $this->afterDuplicate();
        }

        return $this->duplicate;
    }

    protected function beforeDuplicate(): bool
    {
        $event = new DuplicateActiveRecordEvent();
        $event->duplicate = $this->duplicate;

        $this->model->trigger(static::EVENT_BEFORE_CLONE, $event);
        return $event->isValid;
    }

    protected function afterDuplicate(): void
    {
        $event = new DuplicateActiveRecordEvent();
        $event->duplicate = $this->duplicate;

        $this->model->trigger(static::EVENT_AFTER_CLONE, $event);
    }

    protected function getSafeAttributes(): array
    {
        return $this->model->getAttributes($this->model->safeAttributes());
    }

    /**
     * @return T|null
     */
    public static function create(array $params = []): ?ActiveRecord
    {
        $action = Yii::createObject(static::class, $params);
        return $action->duplicate();
    }
}