<?php

namespace davidhirtz\yii2\skeleton\models\actions;

use davidhirtz\yii2\cms\models\ActiveRecord;
use davidhirtz\yii2\skeleton\models\events\DuplicateActiveRecordEvent;

class DuplicateActiveRecordAction
{
    public const EVENT_AFTER_CLONE = 'afterClone';
    public const EVENT_BEFORE_CLONE = 'beforeClone';

    public ActiveRecord $duplicate;

    public function __construct(protected ActiveRecord $model, array $attributes = [])
    {
        $this->duplicate = $this->model::create();
        $this->duplicate->setAttributes([...$this->getSafeAttributes(), ...$attributes], false);

        $this->duplicate();
    }

    protected function duplicate(): void
    {
        if (!$this->beforeDuplicate()) {
            return;
        }

        if ($this->duplicate->insert()) {
            $this->afterDuplicate();
        }
    }

    protected function beforeDuplicate(): bool
    {
        $event = new DuplicateActiveRecordEvent();
        $event->newModel = $this->duplicate;

        $this->model->trigger(static::EVENT_BEFORE_CLONE, $event);
        return $event->isValid;
    }

    protected function afterDuplicate(): void
    {
        $event = new DuplicateActiveRecordEvent();
        $event->newModel = $this->duplicate;

        $this->model->trigger(static::EVENT_AFTER_CLONE, $event);
    }

    protected function getSafeAttributes(): array
    {
        return $this->model->getAttributes($this->model->safeAttributes());
    }
}