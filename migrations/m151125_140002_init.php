<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\models\User;
use Yii;

require(Yii::getAlias('@yii/rbac/migrations') . '/m140506_102106_rbac_init.php');

/**
 * Class m151125_140002_init.
 */
class m151125_140002_init extends \m140506_102106_rbac_init
{
    use \davidhirtz\yii2\skeleton\db\MigrationTrait;

    /**
     * @inheritdoc
     */
    public function up()
    {
        /**
         * Set file permissions to 0777. These should be changed to 0755 with
         * the appropriate user and group rights.
         */
        @chmod(Yii::getAlias('@webroot/assets'), 0777);
        @chmod(Yii::getAlias('@webroot/uploads'), 0777);
        @chmod(Yii::getAlias('@runtime'), 0777);

        /**
         * Create auth tables.
         */
        parent::up();

        /**
         * Vars.
         */
        $auth = $this->getAuthManager();

        /**
         * Changes auth assignment user id to int so it can be
         * used as foreign key constraint.
         */
        $this->alterColumn($auth->assignmentTable, 'user_id', 'int unsigned NOT NULL');

        /**
         * User.
         */
        $this->createTable(User::tableName(), [
            'id' => 'int unsigned NOT NULL AUTO_INCREMENT',
            'status' => 'tinyint(1) NOT NULL DEFAULT "1"',
            'name' => 'varchar(32) NOT NULL',
            'email' => 'varchar(100) NOT NULL',
            'password' => 'varchar(100) DEFAULT NULL',
            'password_salt' => 'varchar(10) DEFAULT NULL',
            'first_name' => 'varchar(50) DEFAULT NULL',
            'last_name' => 'varchar(50) DEFAULT NULL',
            'birthdate' => 'date DEFAULT NULL',
            'city' => 'varchar(50) DEFAULT NULL',
            'country' => 'varchar(50) DEFAULT NULL',
            'picture' => 'varchar(50) DEFAULT NULL',
            'language' => 'varchar(5) NOT NULL DEFAULT "en"',
            'timezone' => 'varchar(100) DEFAULT NULL',
            'email_confirmation_code' => 'varchar(30) DEFAULT NULL',
            'password_reset_code' => 'varchar(30) DEFAULT NULL',
            'is_owner' => 'tinyint(1) unsigned NOT NULL DEFAULT "0"',
            'created_by_user_id' => 'int unsigned DEFAULT NULL',
            'login_count' => 'mediumint unsigned NOT NULL DEFAULT "0"',
            'last_login' => 'datetime DEFAULT NULL',
            'updated_at' => 'datetime DEFAULT NULL',
            'created_at' => 'datetime NOT NULL',
            'PRIMARY KEY ([[id]])',
            'UNIQUE KEY [[name]] ([[name]])',
            'UNIQUE KEY [[email]] ([[email]])',
            'KEY [[status]] ([[status]])',
        ], $this->getTableOptions());

        /**
         * Auth.
         */
        $this->createTable(AuthClient::tableName(), [
            'id' => 'varchar(64) NOT NULL',
            'user_id' => 'int unsigned NOT NULL',
            'name' => 'varchar(10) NOT NULL',
            'data' => 'blob NULL',
            'updated_at' => 'datetime DEFAULT NULL',
            'created_at' => 'datetime NOT NULL',
            'PRIMARY KEY ([[id]], [[name]])',
            'KEY [[user_id]] ([[user_id]])',
        ], $this->getTableOptions());

        /**
         * Login.
         */
        $this->createTable(UserLogin::tableName(), [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT',
            'user_id' => 'int unsigned NOT NULL',
            'type' => 'varchar(12) NOT NULL',
            'browser' => 'varchar(255) NULL',
            'ip' => 'bigint unsigned DEFAULT NULL',
            'created_at' => 'datetime NOT NULL',
            'PRIMARY KEY ([[id]])',
            'KEY [[user_id]] ([[user_id]])',
            'KEY [[ip]] ([[ip]])',
        ], $this->getTableOptions());

        /**
         * Session.
         */
        $this->createTable('{{%session}}', [
            'id' => 'char(64) NOT NULL',
            'user_id' => 'int unsigned DEFAULT NULL',
            'ip' => 'int unsigned NOT NULL DEFAULT "0"',
            'expire' => 'int unsigned DEFAULT NULL',
            'data' => 'longblob',
            'PRIMARY KEY ([[id]])',
            'KEY [[user_id]] ([[user_id]])',
            'KEY [[expire]] ([[expire]])',
        ], $this->getTableOptions());

        /**
         * Cookie authentication key.
         */
        $this->createTable('{{%session_auth_key}}', [
            'id' => 'varchar(64) NOT NULL',
            'user_id' => 'int(10) unsigned NOT NULL',
            'expire' => 'int unsigned DEFAULT NULL',
            'PRIMARY KEY ([[id]])',
            'KEY [[user_id]] ([[user_id]])',
            'KEY [[expire]] ([[expire]])',
        ], $this->getTableOptions());

        /**
         * Foreign keys.
         */
        $this->addForeignKey('auth_client_user_id_ibfk', AuthClient::tableName(), 'user_id', User::tableName(), 'id', 'CASCADE');
        $this->addForeignKey('user_created_by_user_id_ibfk', User::tableName(), 'created_by_user_id', User::tableName(), 'id', 'SET NULL');
        $this->addForeignKey('auth_assignment_user_id_ibfk', $auth->assignmentTable, 'user_id', User::tableName(), 'id', 'CASCADE');
        $this->addForeignKey('login_user_id_ibfk', UserLogin::tableName(), 'user_id', User::tableName(), 'id', 'CASCADE');
        $this->addForeignKey('session_user_id_ibfk', '{{%session}}', 'user_id', User::tableName(), 'id', 'CASCADE');
        $this->addForeignKey('session_auth_key_user_id_ibfk', '{{%session_auth_key}}', 'user_id', User::tableName(), 'id', 'CASCADE');

        /**
         * Authentication data.
         */
        $sourceLanguage = Yii::$app->sourceLanguage;
        $ownerRule = new \davidhirtz\yii2\skeleton\auth\rbac\OwnerRule;
        $auth->add($ownerRule);

        $authUpdate = $auth->createPermission('authUpdate');
        $authUpdate->description = Yii::t('skeleton', 'Assign and revoke user permissions', [], $sourceLanguage);
        $authUpdate->ruleName = $ownerRule->name;
        $auth->add($authUpdate);

        $userUpdate = $auth->createPermission('userUpdate');
        $userUpdate->description = Yii::t('skeleton', 'Update users', [], $sourceLanguage);
        $userUpdate->ruleName = $ownerRule->name;
        $auth->add($userUpdate);

        $userCreate = $auth->createPermission('userCreate');
        $userCreate->description = Yii::t('skeleton', 'Create new users', [], $sourceLanguage);
        $auth->add($userCreate);
        $auth->addChild($userCreate, $userUpdate);

        $userDelete = $auth->createPermission('userDelete');
        $userDelete->description = Yii::t('skeleton', 'Delete users', [], $sourceLanguage);
        $auth->add($userDelete);
        $auth->addChild($userDelete, $userUpdate);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $authUpdate);
        $auth->addChild($admin, $userUpdate);
        $auth->addChild($admin, $userCreate);
        $auth->addChild($admin, $userDelete);

        echo "    > auth data inserted.\n";
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable(AuthClient::tableName());
        $this->dropTable('{{%session_auth_key}}');
        $this->dropTable('{{%session}}');
        $this->dropTable(UserLogin::tableName());

        parent::down();

        $this->dropTable(User::tableName());
    }
}
