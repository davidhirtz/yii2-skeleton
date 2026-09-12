<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Search;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260913120000Search extends Migration
{
    use MigrationTrait;

    /**
     * The table is `utf8mb4` whatever the connection is: a v2 database was migrated as `utf8` (utf8mb3) and a
     * four-byte character in an entry would fail the insert.
     */
    private const string TABLE_OPTIONS = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE InnoDB';

    public function safeUp(): void
    {
        $this->createTable(Search::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'model_class' => $this->string()->notNull(),
            'model_id' => $this->bigInteger()->unsigned()->notNull(),
            'language' => $this->string(16)->notNull(),
            'tenant_id' => $this->integer()->unsigned()->null(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(StatusAttributeInterface::STATUS_ENABLED),
            'weight' => $this->decimal(4, 2)->notNull()->defaultValue(1),
            'title' => $this->string()->notNull(),
            'content' => 'MEDIUMTEXT NULL',
            'updated_at' => $this->dateTime()->notNull(),
        ], self::TABLE_OPTIONS);

        $this->createIndex('model', Search::tableName(), ['model_class', 'model_id', 'language'], true);
        $this->createIndex('tenant', Search::tableName(), ['tenant_id', 'status', 'language']);

        // Yii's query builder only knows unique and non-unique indexes.
        $table = $this->getQuotedTableName(Search::tableName());

        $this->execute("ALTER TABLE $table ADD FULLTEXT INDEX [[title]] ([[title]])");
        $this->execute("ALTER TABLE $table ADD FULLTEXT INDEX [[content]] ([[title]], [[content]])");
    }

    public function safeDown(): void
    {
        $this->dropTable(Search::tableName());
    }
}
