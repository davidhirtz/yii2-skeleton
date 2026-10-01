<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Override;

/**
 * @property string $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property int|null $expire
 * @property string|null $data
 */
class Session extends ActiveRecord
{
    #[Override]
    public static function tableName(): string
    {
        return '{{%session}}';
    }
}
