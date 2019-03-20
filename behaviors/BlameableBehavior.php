<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class BlameableBehavior.
 * @package davidhirtz\yii2\skeleton\behaviors
 */
class BlameableBehavior extends \yii\behaviors\BlameableBehavior
{
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
}