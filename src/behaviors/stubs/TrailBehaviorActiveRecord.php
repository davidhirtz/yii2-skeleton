<?php

namespace davidhirtz\yii2\skeleton\behaviors\stubs;

use davidhirtz\yii2\skeleton\db\ActiveRecord;

abstract class TrailBehaviorActiveRecord extends ActiveRecord
{
    use TrailBehaviorTrait;
}
