<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\rbac\rules\OwnerRule;
use Yii;
use yii\db\Migration;

/**
* @noinspection PhpUnused
*/
class M231105142331OwnerRule extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $owner = new OwnerRule();
        Yii::$app->getAuthManager()->update($owner->name, $owner);
    }

    public function safeDown(): void
    {
    }
}