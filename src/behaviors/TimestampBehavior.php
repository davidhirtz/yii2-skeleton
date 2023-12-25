<?php

namespace davidhirtz\yii2\skeleton\behaviors;

use davidhirtz\yii2\datetime\DateTime;

class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    public function init(): void
    {
        if (!$this->value) {
            $this->value = fn () => new DateTime();
        }

        parent::init();
    }
}
