<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Events;

use Hirtz\Skeleton\Db\ActiveRecord;
use yii\base\ModelEvent;

class DuplicateActiveRecordEvent extends ModelEvent
{
    public ?ActiveRecord $duplicate = null;
}
