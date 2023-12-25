<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * @property ActiveRecord $owner
 */
class BlameableBehavior extends \yii\behaviors\BlameableBehavior
{
    public bool $overwriteChangedValues = false;

    public function init(): void
    {
        if (!$this->attributes) {
            $this->attributes = [
                ActiveRecord::EVENT_BEFORE_INSERT => ['updated_by_user_id'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_by_user_id'],
            ];
        }

        parent::init();
    }

    /**
     * Temporarily removes attributes, that owner changed.
     */
    public function evaluateAttributes($event): void
    {
        $attributes = (array)$this->attributes[$event->name];

        if (!$this->overwriteChangedValues) {
            $this->attributes[$event->name] = [];

            foreach ($attributes as $attribute) {
                if (!$this->owner->isAttributeChanged($attribute)) {
                    $this->attributes[$event->name][] = $attribute;
                }
            }
        }

        parent::evaluateAttributes($event);

        $this->attributes[$event->name] = $attributes;
    }
}
