<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Rbac;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Rbac\DbManager;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class DbManagerTest extends TestCase
{
    use UserFixtureTrait;

    public function testThePermissionsOfAUserMatchTheUncachedLookup(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignManagerRole($user->id);
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);

        $cached = Yii::$app->getAuthManager();
        self::assertInstanceOf(DbManager::class, $cached);

        $uncached = new DbManager(['cache' => null]);

        $expected = array_keys($uncached->getPermissionsByUser($user->id));
        $actual = array_keys($cached->getPermissionsByUser($user->id));

        sort($expected);
        sort($actual);

        self::assertNotEmpty($expected);
        self::assertContains(User::AUTH_USER_ASSIGN, $actual);
        self::assertSame($expected, $actual);
    }

    public function testAUserWithoutAssignmentsHoldsNothing(): void
    {
        $user = $this->getUserFromFixture('disabled');

        self::assertSame([], Yii::$app->getAuthManager()->getPermissionsByUser($user->id));
        self::assertSame([], Yii::$app->getAuthManager()->getPermissionsByUser(''));
    }

    public function testARevokedRoleIsNoLongerReported(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignManagerRole($user->id);

        $auth = Yii::$app->getAuthManager();
        self::assertNotEmpty($auth->getPermissionsByUser($user->id));

        $auth->revokeAll($user->id);

        self::assertSame([], $auth->getPermissionsByUser($user->id));
    }
}
