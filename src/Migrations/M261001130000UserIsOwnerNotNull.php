<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261001130000UserIsOwnerNotNull extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->update('{{%user}}', ['is_owner' => 0], ['is_owner' => null]);
        $this->alterColumn('{{%user}}', 'is_owner', 'tinyint(1) unsigned NOT NULL DEFAULT 0');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->alterColumn('{{%user}}', 'is_owner', 'tinyint(1) unsigned DEFAULT 0');
    }
}
