<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Trail;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913110000TrailType extends Migration
{
    use MigrationTrait;

    private const int LEGACY_TYPE_DEFAULT = 1;

    public function safeUp(): void
    {
        $this->alterColumn(
            Trail::tableName(),
            'type',
            (string)$this->smallInteger()->unsigned()->notNull()->defaultValue(Trail::TYPE_DEFAULT)
        );
    }

    public function safeDown(): void
    {
        $this->alterColumn(
            Trail::tableName(),
            'type',
            (string)$this->smallInteger()->unsigned()->notNull()->defaultValue(self::LEGACY_TYPE_DEFAULT)
        );
    }
}
