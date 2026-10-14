<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261014120000TwoFactorTimeslice extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'two_factor_timeslice', 'int(10) unsigned DEFAULT NULL AFTER `two_factor_secret`');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'two_factor_timeslice');
    }
}
