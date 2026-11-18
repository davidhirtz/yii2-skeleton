<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261118120000SessionHandoffTag extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->addColumn('{{%session}}', 'handoff_tag', 'char(64) NULL DEFAULT NULL AFTER `ip_address`');
        $this->createIndex('handoff_tag', '{{%session}}', 'handoff_tag');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropIndex('handoff_tag', '{{%session}}');
        $this->dropColumn('{{%session}}', 'handoff_tag');
    }
}
