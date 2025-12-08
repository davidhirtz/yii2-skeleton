<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Override;

/**
 * @property string $id
 * @property int $user_id
 * @property string $ip_address
 * @property int $expire
 * @property string $data
 */
class Session extends ActiveRecord
{
    #[Override]
    public static function tableName(): string
    {
        return '{{%session}}';
    }
}
