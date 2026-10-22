<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Migrations;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Override;
use RuntimeException;
use Yii;

class ManagerRoleMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;

    private const string MIGRATION = 'Hirtz\\Skeleton\\Migrations\\M260914190000ManagerRole';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireUpgradeMigration('yii2-skeleton', self::MIGRATION);

        $auth = Yii::$app->getAuthManager();
        $manager = $auth->getRole(User::AUTH_ROLE_MANAGER);

        if ($manager !== null) {
            $auth->remove($manager);
        }
    }

    public function testTheManagerHoldsEveryPermissionButTheTenant(): void
    {
        $this->migrate();

        $auth = Yii::$app->getAuthManager();
        $names = array_keys($auth->getChildren(User::AUTH_ROLE_MANAGER));

        self::assertContains(User::AUTH_USER_ASSIGN, $names);
        self::assertNotContains('tenant', $names);
    }

    public function testAProjectsOwnManagerRoleIsRefused(): void
    {
        $auth = Yii::$app->getAuthManager();

        $manager = $auth->createRole(User::AUTH_ROLE_MANAGER);
        $auth->add($manager);

        $permission = $auth->createPermission('reportView');
        $auth->add($permission);
        $auth->addChild($manager, $permission);

        try {
            $this->migrate();
            self::fail('The project\'s own manager role was extended.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('rename it', $exception->getMessage());
        }

        self::assertSame(['reportView'], array_keys($auth->getChildren(User::AUTH_ROLE_MANAGER)));
    }

    private function migrate(): void
    {
        $this->createUpgradeMigration('yii2-skeleton', self::MIGRATION, ['compact' => true])->safeUp();
    }
}
