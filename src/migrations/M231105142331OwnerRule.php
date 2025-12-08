<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\migrations;

use Hirtz\Skeleton\db\traits\MigrationTrait;
use Hirtz\Skeleton\rbac\rules\OwnerRule;
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
