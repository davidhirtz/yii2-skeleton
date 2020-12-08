<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\MigrationTrait;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\db\Migration;

/**
 * Class M201205112000Trail
 * @package davidhirtz\yii2\skeleton\migrations
 * @noinspection PhpUnused
 */
class M201205112000Trail extends Migration
{
    use MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp()
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
        $admin = $auth->getRole('admin');

        $trailIndex = $auth->createPermission('trailIndex');
        $trailIndex->description = Yii::t('skeleton', 'View history', [], $sourceLanguage);
        $auth->add($trailIndex);

        $auth->addChild($admin, $trailIndex);
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        $this->dropTable(Trail::tableName());

        $auth = Yii::$app->getAuthManager();
        $this->delete($auth->itemTable, ['name' => 'trailIndex']);
    }
}