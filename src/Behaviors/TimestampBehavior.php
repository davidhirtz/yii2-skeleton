<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\DateTime;

class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    #[\Override]
    public function init(): void
    {
        if (!$this->value) {
            $this->value = fn () => new DateTime();
        }

        parent::init();
    }
}
