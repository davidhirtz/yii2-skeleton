<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M210831105800GoogleAuthenticator extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->addColumn(User::tableName(), 'google_2fa_secret', (string)$this->string(16)
            ->null()
            ->after('password_reset_code'));
    }
    
    public function safeDown(): void
    {
        $this->dropColumn(User::tableName(), 'google_2fa_secret');
    }
}
