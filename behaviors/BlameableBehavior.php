<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class BlameableBehavior
 * @package davidhirtz\yii2\skeleton\behaviors
 *
 * @property ActiveRecord $owner
 */
class BlameableBehavior extends \yii\behaviors\BlameableBehavior
{
    /**
     * @var bool
     */
    public $overwriteChangedValues = false;

    /**
     * @inheritdoc
     */
    public function init()
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
     * Temporarily removes attributes that where changed by owner.
     * @inheritDoc
     */
    public function evaluateAttributes($event)
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