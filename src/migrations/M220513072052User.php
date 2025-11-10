<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\User;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */

class M220513072052User extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->renameColumn(User::tableName(), 'password', 'password_hash');

        $this->renameColumn(User::tableName(), 'email_confirmation_code', 'verification_token');
        $this->alterColumn(User::tableName(), 'verification_token', (string)$this->string(32)->null());

        $this->renameColumn(User::tableName(), 'password_reset_code', 'password_reset_token');
        $this->alterColumn(User::tableName(), 'password_reset_token', (string)$this->string(32)->null());

        // Clean up for yii2-skeleton 1.3.X
        $schema = $this->getDb()->getSchema();

        if (!$schema->getTableSchema(User::tableName())->getColumn('auth_key')) {
            $this->addColumn(User::tableName(), 'auth_key', (string)$this->string(32)
                ->null()
                ->after('timezone'));
        }

        if ($this->db->getSchema()->getTableSchema('{{%session_auth_key}}')) {
            $this->dropTable('{{%session_auth_key}}');
        }
    }
    
    public function safeDown(): void
    {
        $this->renameColumn(User::tableName(), 'password_hash', 'password');

        $this->renameColumn(User::tableName(), 'verification_token', 'email_confirmation_code');
        $this->renameColumn(User::tableName(), 'password_reset_token', 'password_reset_code');
    }
}
