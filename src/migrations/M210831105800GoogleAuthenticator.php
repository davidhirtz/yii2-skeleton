<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use yii\db\Migration;

/**
* Class M210831105800GoogleAuthenticator
* @package davidhirtz\yii2\skeleton\migrations
* @noinspection PhpUnused
*/
class M210831105800GoogleAuthenticator extends Migration
{
    use MigrationTrait;

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        $this->addColumn(User::tableName(), 'google_2fa_secret', $this->string(16)->null()->after('password_reset_code'));
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        $this->dropColumn(User::tableName(), 'google_2fa_secret');
    }
}