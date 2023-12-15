<?php

/** @noinspection PhpUnused */

namespace davidhirtz\yii2\skeleton\migrations;

use davidhirtz\yii2\skeleton\db\traits\MigrationTrait;
use davidhirtz\yii2\skeleton\models\AuthClient;
use davidhirtz\yii2\skeleton\models\Session;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\rbac\rules\OwnerRule;
use Yii;
use yii\db\Migration;
use yii\db\Query;

/**
 * @noinspection PhpUnused
 */

class M190125140002Init extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        if ($this->isMigrationApplied()) {
            return;
        }

        $authManager = $this->getAuthManager();
        $tableOptions = $this->getTableOptions();

        $this->createTable($authManager->ruleTable, [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->addPrimaryKey('name', $authManager->ruleTable, 'name');

        $this->createTable($authManager->itemTable, [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'updated_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('name', $authManager->itemTable, 'name');

        $this->addForeignKey(
            'auth_item_rule_name_ibfk',
            $authManager->itemTable,
            'rule_name',
            $authManager->ruleTable,
            'name',
            'SET NULL',
            'CASCADE'
        );

        $this->createIndex('type', $authManager->itemTable, 'type');

        $this->createTable($authManager->itemChildTable, [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[parent]], [[child]])',
        ], $tableOptions);

        $this->addForeignKey(
            'auth_item_child_parent_ibfk',
            $authManager->itemChildTable,
            'parent',
            $authManager->itemTable,
            'name',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'auth_item_child_child_ibfk',
            $authManager->itemChildTable,
            'child',
            $authManager->itemTable,
            'name',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable($authManager->assignmentTable, [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->integer(),
            'PRIMARY KEY ([[item_name]], [[user_id]])',
        ], $tableOptions);

        $this->addForeignKey(
            'auth_assignment_item_name_ibfk',
            $authManager->assignmentTable,
            'item_name',
            $authManager->itemTable,
            'name',
            'CASCADE',
            'CASCADE'
        );

        /**
         * User.
         */
        $isNameRequired = User::instance()->requireName;

        $this->createTable(User::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(User::STATUS_DEFAULT),
            'name' => $isNameRequired ? $this->string(32)->notNull() : $this->string(32)->null(),
            'email' => $this->string(100)->notNull(),
            'password' => $this->string(100)->null(),
            'password_salt' => $this->string(10)->null(),
            'first_name' => $this->string(50)->null(),
            'last_name' => $this->string(50)->null(),
            'birthdate' => $this->date()->null(),
            'city' => $this->string(50)->null(),
            'country' => $this->string(2)->null(),
            'picture' => $this->string(50)->null(),
            'language' => $this->string(5)->notNull()->defaultValue(Yii::$app->sourceLanguage),
            'timezone' => $this->string(100)->null(),
            'auth_key' => $this->string(32)->notNull(),
            'email_confirmation_code' => $this->string(30)->null(),
            'password_reset_code' => $this->string(30)->null(),
            'is_owner' => $this->boolean()->unsigned()->defaultValue(0),
            'created_by_user_id' => $this->integer()->unsigned()->null(),
            'login_count' => $this->smallInteger()->notNull()->defaultValue(0),
            'last_login' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('status', User::tableName(), 'status');
        $this->createIndex('email', User::tableName(), 'email', true);
        
        if ($isNameRequired) {
            $this->createIndex('name', User::tableName(), 'name', true);
        }
        
        /**
         * Auth.
         */
        $this->createTable(AuthClient::tableName(), [
            'id' => $this->string(64)->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(10)->notNull(),
            'data' => 'blob NULL',
            'updated_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]], [[name]])'
        ], $tableOptions);

        $this->createIndex('user_id', AuthClient::tableName(), 'user_id');

        /**
         * Login.
         */
        $this->createTable(UserLogin::tableName(), [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->string(12)->notNull(),
            'browser' => $this->string()->null(),
            'ip_address' => 'varbinary(16) NULL',
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('user_id', UserLogin::tableName(), 'user_id');

        /**
         * Session.
         */
        $this->createTable(Session::tableName(), [
            'id' => $this->char(64)->notNull(),
            'user_id' => $this->integer()->unsigned()->null(),
            'ip_address' => 'varbinary(16) NULL',
            'expire' => $this->integer()->unsigned()->null(),
            'data' => 'longblob',
        ], $tableOptions);

        $this->addPrimaryKey('id', Session::tableName(), 'id');
        $this->createIndex('user_id', Session::tableName(), 'user_id');
        $this->createIndex('expire', Session::tableName(), 'expire');

        /**
         * Foreign keys.
         */
        $this->addForeignKey(
            'auth_client_user_id_ibfk',
            AuthClient::tableName(),
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'user_created_by_user_id_ibfk',
            User::tableName(),
            'created_by_user_id',
            User::tableName(),
            'id',
            'SET NULL'
        );

        $this->addForeignKey(
            'auth_assignment_user_id_ibfk',
            $authManager->assignmentTable,
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'login_user_id_ibfk',
            UserLogin::tableName(),
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'session_user_id_ibfk',
            Session::tableName(),
            'user_id',
            User::tableName(),
            'id',
            'CASCADE'
        );

        /**
         * Authentication data.
         */
        $sourceLanguage = Yii::$app->sourceLanguage;
        $ownerRule = new OwnerRule();
        $authManager->add($ownerRule);

        $authUpdate = $authManager->createPermission(User::AUTH_USER_ASSIGN);
        $authUpdate->description = Yii::t('skeleton', 'Assign and revoke user permissions', [], $sourceLanguage);
        $authUpdate->ruleName = $ownerRule->name;
        $authManager->add($authUpdate);

        $userUpdate = $authManager->createPermission(User::AUTH_USER_UPDATE);
        $userUpdate->description = Yii::t('skeleton', 'Update users', [], $sourceLanguage);
        $userUpdate->ruleName = $ownerRule->name;
        $authManager->add($userUpdate);

        $userCreate = $authManager->createPermission(User::AUTH_USER_CREATE);
        $userCreate->description = Yii::t('skeleton', 'Create new users', [], $sourceLanguage);
        $authManager->add($userCreate);
        $authManager->addChild($userCreate, $userUpdate);

        $userDelete = $authManager->createPermission(User::AUTH_USER_DELETE);
        $userDelete->description = Yii::t('skeleton', 'Delete users', [], $sourceLanguage);
        $authManager->add($userDelete);
        $authManager->addChild($userDelete, $userUpdate);

        $admin = $authManager->createRole(User::AUTH_ROLE_ADMIN);
        $authManager->add($admin);
        $authManager->addChild($admin, $authUpdate);
        $authManager->addChild($admin, $userUpdate);
        $authManager->addChild($admin, $userCreate);
        $authManager->addChild($admin, $userDelete);

        echo "    > auth data inserted.\n";
    }

    public function safeDown(): void
    {
        if ($this->isMigrationApplied()) {
            return;
        }

        $authManager = $this->getAuthManager();

        $this->dropTable(AuthClient::tableName());
        $this->dropTable(Session::tableName());
        $this->dropTable(UserLogin::tableName());

        $this->dropTable($authManager->assignmentTable);
        $this->dropTable($authManager->itemChildTable);
        $this->dropTable($authManager->itemTable);
        $this->dropTable($authManager->ruleTable);

        $this->dropTable(User::tableName());
    }

    protected function isMigrationApplied(): bool
    {
        return (new Query())->from('{{%migration}}')
            ->where(['version' => 'davidhirtz\yii2\skeleton\migrations\m151125_140002_init'])
            ->exists();
    }
}
