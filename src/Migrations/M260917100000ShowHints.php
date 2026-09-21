<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260917100000ShowHints extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumnIfMissing(
            User::tableName(),
            'show_hints',
            (string)$this->boolean()->unsigned()->notNull()->defaultValue(1)->after('timezone')
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn(User::tableName(), 'show_hints');
    }
}
