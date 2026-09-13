<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913190000TwoFactorSecret extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->renameColumn(User::tableName(), 'google_2fa_secret', 'two_factor_secret');
    }

    public function safeDown(): void
    {
        $this->renameColumn(User::tableName(), 'two_factor_secret', 'google_2fa_secret');
    }
}
