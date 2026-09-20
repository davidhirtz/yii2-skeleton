<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260920100000ColorScheme extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumn(
            User::tableName(),
            'color_scheme',
            (string)$this->string(5)->after('show_hints')
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn(User::tableName(), 'color_scheme');
    }
}
