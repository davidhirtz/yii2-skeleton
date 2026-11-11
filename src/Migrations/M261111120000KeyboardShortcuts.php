<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261111120000KeyboardShortcuts extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'keyboard_shortcuts', 'tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER `show_hints`');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'keyboard_shortcuts');
    }
}
