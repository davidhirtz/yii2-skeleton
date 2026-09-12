<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260912130000AuthClient extends Migration
{
    use MigrationTrait;

    private const string TABLE_NAME = '{{%auth_client}}';
    private const string MODEL_CLASS = 'Hirtz\Skeleton\Models\AuthClient';

    public function safeUp(): void
    {
        $this->deleteTrails();
        $this->dropTable(self::TABLE_NAME);
    }

    public function safeDown(): void
    {
        $this->createTable(self::TABLE_NAME, [
            'id' => $this->string(64)->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(10)->notNull(),
            'data' => 'blob NULL',
            'updated_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]], [[name]])'
        ], $this->getTableOptions());

        $this->createIndex('user_id', self::TABLE_NAME, 'user_id');

        $this->addForeignKey(
            'auth_client_user_id_ibfk',
            self::TABLE_NAME,
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );
    }

    /**
     * The records are gone, so a trail pointing at one would only log an unresolvable class on every trail index.
     */
    protected function deleteTrails(): void
    {
        $this->delete(Trail::tableName(), ['model_class' => self::MODEL_CLASS]);

        $trail = $this->getQuotedTableName(Trail::tableName());
        $modelClass = $this->getDb()->quoteValue(self::MODEL_CLASS);

        $this->execute("
            DELETE FROM $trail
            WHERE JSON_UNQUOTE(JSON_EXTRACT([[data]], '$.model_class')) = $modelClass
        ");
    }
}
