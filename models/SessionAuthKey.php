<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * Class SessionAuthKey
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $id
 * @property int $user_id
 * @property int $expire
 */
class SessionAuthKey extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%session_auth_key}}';
    }
}