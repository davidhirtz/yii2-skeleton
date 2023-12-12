<?php

namespace davidhirtz\yii2\skeleton\models\stubs;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\traits\StatusAttributeTrait;

abstract class StatusAttributeActiveRecord extends ActiveRecord
{
    use StatusAttributeTrait;
}