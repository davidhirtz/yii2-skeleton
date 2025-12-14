<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Yii;
use yii\db\Migration;

/**
 * @package Hirtz\Skeleton\Migrations
 * @noinspection PhpUnused
 */
class M201205112000Trail extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->createTable(Trail::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'type' => $this->smallInteger()->unsigned()->notNull()->defaultValue(Trail::TYPE_DEFAULT),
            'model' => $this->string()->null(),
            'model_id' => $this->string(64)->null(),
            'user_id' => $this->integer()->unsigned()->null(),
            'message' => $this->string()->null(),
            'data' => $this->json()->null(),
            'created_at' => $this->dateTime()->notNull()
        ], $this->getTableOptions());

        $this->createIndex('model', Trail::tableName(), ['model', 'model_id']);
        $this->createIndex('user_id', Trail::tableName(), ['user_id']);

        $sourceLanguage = Yii::$app->sourceLanguage;
        $auth = Yii::$app->getAuthManager();
        $admin = $auth->getRole(User::AUTH_ROLE_ADMIN);

        $trailIndex = $auth->createPermission(Trail::AUTH_TRAIL_INDEX);
        $trailIndex->description = Yii::t('skeleton', 'View history', [], $sourceLanguage);
        $auth->add($trailIndex);

        $auth->addChild($admin, $trailIndex);
    }

    public function safeDown(): void
    {
        $this->dropTable(Trail::tableName());

        $auth = Yii::$app->getAuthManager();
        $this->delete($auth->itemTable, ['name' => Trail::AUTH_TRAIL_INDEX]);
    }
}
