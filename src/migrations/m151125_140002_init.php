<?php

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\rbac\rules\OwnerRule;
use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\models\User;
use m140506_102106_rbac_init;
use Yii;

require(Yii::getAlias('@yii/rbac/migrations') . '/m140506_102106_rbac_init.php');

/**
 * Class m151125_140002_init.
 */
class m151125_140002_init extends m140506_102106_rbac_init
{
    use MigrationTrait;

    /**
     * @inheritdoc
     */
    public function up()
    {
        parent::up();

        // Changes auth assignment user id to int, so it can be used as foreign key constraint.
        $auth = $this->getAuthManager();
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
            'country' => 'varchar(2) DEFAULT NULL',
            'picture' => 'varchar(50) DEFAULT NULL',
            'language' => 'varchar(5) NOT NULL DEFAULT "en"',
            'timezone' => 'varchar(100) DEFAULT NULL',
            'auth_key' => 'varchar(32) NOT NULL',
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
            'ip_address' => 'varbinary(16) NULL',
            'created_at' => 'datetime NOT NULL',
            'PRIMARY KEY ([[id]])',
            'KEY [[user_id]] ([[user_id]])',
        ], $this->getTableOptions());

        /**
         * Session.
         */
        $this->createTable(Session::tableName(), [
            'id' => 'char(64) NOT NULL',
            'user_id' => 'int unsigned DEFAULT NULL',
            'ip_address' => 'varbinary(16) NULL',
            'expire' => 'int unsigned DEFAULT NULL',
            'data' => 'longblob',
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
        $this->addForeignKey('session_user_id_ibfk', Session::tableName(), 'user_id', User::tableName(), 'id', 'CASCADE');

        /**
         * Authentication data.
         */
        $sourceLanguage = Yii::$app->sourceLanguage;
        $ownerRule = new OwnerRule();
        $auth->add($ownerRule);

        $authUpdate = $auth->createPermission(User::AUTH_USER_ASSIGN);
        $authUpdate->description = Yii::t('skeleton', 'Assign and revoke user permissions', [], $sourceLanguage);
        $authUpdate->ruleName = $ownerRule->name;
        $auth->add($authUpdate);

        $userUpdate = $auth->createPermission(User::AUTH_USER_UPDATE);
        $userUpdate->description = Yii::t('skeleton', 'Update users', [], $sourceLanguage);
        $userUpdate->ruleName = $ownerRule->name;
        $auth->add($userUpdate);

        $userCreate = $auth->createPermission(User::AUTH_USER_CREATE);
        $userCreate->description = Yii::t('skeleton', 'Create new users', [], $sourceLanguage);
        $auth->add($userCreate);
        $auth->addChild($userCreate, $userUpdate);

        $userDelete = $auth->createPermission(User::AUTH_USER_DELETE);
        $userDelete->description = Yii::t('skeleton', 'Delete users', [], $sourceLanguage);
        $auth->add($userDelete);
        $auth->addChild($userDelete, $userUpdate);

        $admin = $auth->createRole(User::AUTH_ROLE_ADMIN);
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
        $this->dropTable(Session::tableName());
        $this->dropTable(UserLogin::tableName());

        parent::down();

        $this->dropTable(User::tableName());
    }
}
