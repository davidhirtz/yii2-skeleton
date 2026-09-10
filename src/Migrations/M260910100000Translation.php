<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Translation;
use yii\db\Migration;

/**
 * Creates the {@see Translation} table. The bundle migrations that move the `_xx` columns into it run afterwards, so
 * this timestamp must stay below theirs — Yii sorts all migration namespaces together by timestamp.
 *
 * @noinspection PhpUnused
 */
class M260910100000Translation extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->createTable(Translation::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'model' => $this->string()->notNull(),
            'model_id' => $this->bigInteger()->unsigned()->notNull(),
            'language' => $this->string(16)->notNull(),
            'attribute' => $this->string(64)->notNull(),
            'value' => $this->text()->null(),
        ], $this->getTableOptions());

        $this->createIndex('model', Translation::tableName(), ['model', 'model_id', 'language', 'attribute'], true);
    }

    public function safeDown(): void
    {
        $this->dropTable(Translation::tableName());
    }
}
