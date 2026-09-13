<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Db;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\db\Migration;

class MigrationTraitAuthItemsTest extends TestCase
{
    use UserFixtureTrait;

    public function testAPermissionIsCreatedWithItsMessagePointerAndParents(): void
    {
        $migration = $this->createMigration();
        $migration->addPermission('testCreated', Message::make('skeleton', 'AUTH_USER_DESCRIPTION'), User::AUTH_ROLE_ADMIN);

        $auth = Yii::$app->getAuthManager();
        $permission = $auth->getPermission('testCreated');

        self::assertSame('{"category":"skeleton","key":"AUTH_USER_DESCRIPTION"}', $permission->description);
        self::assertArrayHasKey('testCreated', $auth->getPermissionsByRole(User::AUTH_ROLE_ADMIN));
    }

    public function testTheNewItemTakesOverEveryParentAndAssignee(): void
    {
        $auth = Yii::$app->getAuthManager();
        $user = $this->getUserFromFixture('admin');

        $migration = $this->createMigration();
        $description = Message::make('skeleton', 'AUTH_USER_DESCRIPTION');

        $migration->addPermission('testOldCreate', $description, User::AUTH_ROLE_ADMIN);
        $migration->addPermission('testOldUpdate', $description);

        $this->assignPermission($user->id, 'testOldUpdate');

        $migration->addPermission('testNew', $description);
        $migration->replaceAuthItems(['testOldCreate', 'testOldUpdate'], 'testNew');

        self::assertNull($auth->getPermission('testOldCreate'));
        self::assertNull($auth->getPermission('testOldUpdate'));

        self::assertArrayHasKey('testNew', $auth->getPermissionsByRole(User::AUTH_ROLE_ADMIN));
        self::assertArrayHasKey('testNew', $auth->getAssignments($user->id));
    }

    public function testTheOldItemsAreRestoredUnderTheSameParentsAndAssignees(): void
    {
        $auth = Yii::$app->getAuthManager();
        $user = $this->getUserFromFixture('admin');

        $migration = $this->createMigration();
        $description = Message::make('skeleton', 'AUTH_USER_DESCRIPTION');

        $migration->addPermission('testOldCreate', $description, User::AUTH_ROLE_ADMIN);
        $migration->addPermission('testOldUpdate', $description, User::AUTH_ROLE_ADMIN);

        $this->assignPermission($user->id, 'testOldUpdate');

        $migration->addPermission('testNew', $description);
        $migration->replaceAuthItems(['testOldCreate', 'testOldUpdate'], 'testNew');
        $migration->restoreAuthItems(['testOldCreate', 'testOldUpdate'], 'testNew', $description);

        self::assertNull($auth->getPermission('testNew'));

        $permissions = $auth->getPermissionsByRole(User::AUTH_ROLE_ADMIN);

        self::assertArrayHasKey('testOldCreate', $permissions);
        self::assertArrayHasKey('testOldUpdate', $permissions);

        $assignments = $auth->getAssignments($user->id);

        self::assertArrayHasKey('testOldCreate', $assignments);
        self::assertArrayHasKey('testOldUpdate', $assignments);
    }

    private function createMigration(): TestAuthItemMigration
    {
        return new TestAuthItemMigration(['compact' => true]);
    }
}

class TestAuthItemMigration extends Migration
{
    use MigrationTrait {
        addPermission as public;
        replaceAuthItems as public;
        restoreAuthItems as public;
    }
}
