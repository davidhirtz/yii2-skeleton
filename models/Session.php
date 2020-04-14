<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class Session
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $id
 * @property int $user_id
 * @property string $ip_address
 * @property int $expire
 * @property string $data
 */
class Session extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%session}}';
    }
}