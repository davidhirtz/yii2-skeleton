<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\behaviors;

use davidhirtz\yii2\datetime\DateTime;

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
