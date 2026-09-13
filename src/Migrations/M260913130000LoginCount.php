<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913130000LoginCount extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->alterColumn(
            User::tableName(),
            'login_count',
            (string)$this->integer()->unsigned()->notNull()->defaultValue(0)
        );
    }

    public function safeDown(): void
    {
        $this->alterColumn(
            User::tableName(),
            'login_count',
            (string)$this->smallInteger()->notNull()->defaultValue(0)
        );
    }
}
