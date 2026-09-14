<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * Cosmetic: the JSON column reads as part of the record rather than as an afterthought behind the timestamps.
 *
 * @noinspection PhpUnused
 */
class M260915130000CustomAttributesColumn extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->moveCustomAttributesColumn(User::tableName(), 'two_factor_secret');
    }

    public function safeDown(): void
    {
        $this->moveCustomAttributesColumnToEnd(User::tableName());
    }
}
