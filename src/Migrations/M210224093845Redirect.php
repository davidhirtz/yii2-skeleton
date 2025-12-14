<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Yii;
use yii\db\Migration;

/**
 * @package Hirtz\Skeleton\Migrations
 * @noinspection PhpUnused
 */
class M210224093845Redirect extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->createTable(Redirect::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'type' => $this->smallInteger()->notNull()->defaultValue(Redirect::TYPE_DEFAULT),
            'request_uri' => $this->string(250)->notNull()->unique(),
            'url' => $this->string(250)->notNull(),
            'updated_by_user_id' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
        ]);

        $auth = Yii::$app->getAuthManager();
        $admin = $auth->getRole(User::AUTH_ROLE_ADMIN);

        $redirectCreate = $auth->createPermission(Redirect::AUTH_REDIRECT_CREATE);
        $redirectCreate->description = Yii::t('skeleton', 'Create and update redirect rules', [], Yii::$app->sourceLanguage);
        $auth->add($redirectCreate);

        $auth->addChild($admin, $redirectCreate);
    }

    public function safeDown(): void
    {
        $this->dropTable(Redirect::tableName());

        $auth = Yii::$app->getAuthManager();
        $this->delete($auth->itemTable, ['name' => Redirect::AUTH_REDIRECT_CREATE]);
    }
}
