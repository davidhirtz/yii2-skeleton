<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\actions;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\events\DuplicateActiveRecordEvent;
use Exception;
use Yii;

/**
 * @template T of ActiveRecord
 * @property T $model
 * @property T $duplicate
 */
class DuplicateActiveRecord
{
    public const EVENT_AFTER_DUPLICATE = 'afterDuplicate';
    public const EVENT_BEFORE_DUPLICATE = 'beforeDuplicate';

    public ActiveRecord $duplicate;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $this->duplicate = $this->model::create();
        $this->duplicate->setAttributes([...$this->getSafeAttributes(), ...$attributes], false);
    }

    public function duplicateActiveRecord(): bool
    {
        if ($this->model::getDb()->getTransaction()) {
            return $this->duplicateInternal();
        }

        $transaction = $this->model::getDb()->beginTransaction();

        try {
            if ($this->duplicateInternal()) {
                $transaction->commit();
                return true;
            }
        } catch (Exception $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return false;
    }

    protected function duplicateInternal(): bool
    {
        if ($this->beforeDuplicate() && $this->duplicate->insert()) {
            $this->afterDuplicate();
            return true;
        }

        return false;
    }

    protected function beforeDuplicate(): bool
    {
        $event = new DuplicateActiveRecordEvent();
        $event->duplicate = $this->duplicate;

        $this->model->trigger(static::EVENT_BEFORE_DUPLICATE, $event);
        return $event->isValid;
    }

    protected function afterDuplicate(): void
    {
        $event = new DuplicateActiveRecordEvent();
        $event->duplicate = $this->duplicate;

        $this->model->trigger(static::EVENT_AFTER_DUPLICATE, $event);
    }

    protected function getSafeAttributes(): array
    {
        return $this->model->getAttributes($this->model->safeAttributes());
    }

    /**
     * @return T
     * @noinspection PhpDocSignatureInspection
     */
    public static function create(array $params = []): ActiveRecord
    {
        $action = Yii::createObject(static::class, $params);
        $action->duplicateActiveRecord();

        return $action->duplicate;
    }
}
