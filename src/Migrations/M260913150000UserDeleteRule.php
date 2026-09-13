<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * The rule this attached is gone in 3.0; `M260914100000AuthItems` empties the table it lived in and clears every
 * `rule_name`.
 *
 * @noinspection PhpUnused
 */
class M260913150000UserDeleteRule extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
    }

    public function safeDown(): void
    {
    }
}
