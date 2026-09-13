<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * The rule this re-saved is gone in 3.0; `M260914100000AuthItems` empties the table it lived in.
 *
 * @noinspection PhpUnused
 */
class M231105142331OwnerRule extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
    }

    public function safeDown(): void
    {
    }
}
