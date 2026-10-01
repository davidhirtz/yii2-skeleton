<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * Every rename looks its redirects up by target, once per file of a renamed folder.
 *
 * @noinspection PhpUnused
 */
class M261001120000RedirectUrlIndex extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->createIndex('url', '{{%redirect}}', 'url');
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropIndex('url', '{{%redirect}}');
    }
}
