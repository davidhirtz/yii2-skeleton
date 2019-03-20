<?php

namespace davidhirtz\yii2\skeleton\behaviors;


use davidhirtz\yii2\datetime\DateTime;

/**
 * Class TimestampBehavior.
 * @package davidhirtz\yii2\skeleton\behaviors
 */
class TimestampBehavior extends \yii\behaviors\TimestampBehavior
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->value) {
            $this->value = function () {
                return new DateTime;
            };
        }

        parent::init();
    }
}