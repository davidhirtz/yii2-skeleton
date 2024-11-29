<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\db\ActiveRecord;

/**
 * @property string $id
 * @property int $user_id
 * @property string $ip_address
 * @property int $expire
 * @property string $data
 */
class Session extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%session}}';
    }
}
