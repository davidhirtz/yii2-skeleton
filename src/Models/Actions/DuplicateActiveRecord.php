<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Events\DuplicateActiveRecordEvent;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
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

    /**
     * @var T
     */
    public ActiveRecord $duplicate;

    /**
     * @param T $model
     * @param array<string, mixed> $attributes
     */
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
        $this->duplicateCustomAttributes();

        $event = new DuplicateActiveRecordEvent();
        $event->duplicate = $this->duplicate;

        $this->model->trigger(static::EVENT_AFTER_DUPLICATE, $event);
    }

    /**
     * The duplicate was inserted with the source's values, so a definition storing something of its own outside the
     * column is pointing at what the source owns until it is told.
     */
    protected function duplicateCustomAttributes(): void
    {
        if (!$this->duplicate instanceof CustomAttributeInterface || !$this->model instanceof CustomAttributeInterface) {
            return;
        }

        foreach ($this->duplicate->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this->duplicate) as $name) {
                $definition->afterDuplicate($this->duplicate, $this->model, $name);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSafeAttributes(): array
    {
        return $this->model->getAttributes($this->model->safeAttributes());
    }

    /**
     * @param array<int|string, mixed> $params
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
