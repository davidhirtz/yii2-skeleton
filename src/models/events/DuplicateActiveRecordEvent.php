<?php

namespace davidhirtz\yii2\skeleton\models\events;

use davidhirtz\yii2\cms\models\ActiveRecord;
use yii\base\ModelEvent;

class DuplicateActiveRecordEvent extends ModelEvent
{
    public ?ActiveRecord $newModel = null;
}