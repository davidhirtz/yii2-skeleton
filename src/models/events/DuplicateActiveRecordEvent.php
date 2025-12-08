<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models\events;

use Hirtz\Skeleton\db\ActiveRecord;
use yii\base\ModelEvent;

class DuplicateActiveRecordEvent extends ModelEvent
{
    public ?ActiveRecord $duplicate = null;
}
