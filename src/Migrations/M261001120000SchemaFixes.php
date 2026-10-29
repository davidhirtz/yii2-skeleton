<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * Indexes `redirect.url`, since every rename looks redirects up by it once per file of a renamed folder.
 *
 * @noinspection PhpUnused
 */
class M261001120000SchemaFixes extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->alterColumn('{{%redirect}}', 'request_uri', 'varchar(512) NOT NULL');
        $this->alterColumn('{{%redirect}}', 'url', 'varchar(512) NOT NULL');
        $this->createIndex('url', '{{%redirect}}', 'url');

        $this->update('{{%user}}', ['is_owner' => 0], ['is_owner' => null]);
        $this->alterColumn('{{%user}}', 'is_owner', 'tinyint(1) unsigned NOT NULL DEFAULT 0');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->alterColumn('{{%user}}', 'is_owner', 'tinyint(1) unsigned DEFAULT 0');
        $this->dropIndex('url', '{{%redirect}}');
        $this->alterColumn('{{%redirect}}', 'url', 'varchar(250) NOT NULL');
        $this->alterColumn('{{%redirect}}', 'request_uri', 'varchar(250) NOT NULL');
    }
}
