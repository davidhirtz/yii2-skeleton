<?php

namespace davidhirtz\yii2\skeleton\models\events;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use yii\base\ModelEvent;

class DuplicateActiveRecordEvent extends ModelEvent
{
    public ?ActiveRecord $duplicate = null;
}
