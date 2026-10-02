<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

/**
 * The user and login grids ask for every row whether the actor may manage its user.
 */
class UserCanManageQueryCountTest extends TestCase
{
    use UserFixtureTrait;

    public function testAnAdministratorPaysNoQueryPerTarget(): void
    {
        $this->loginAs(User::AUTH_ROLE_ADMIN);

        self::assertSame(0, $this->countQueriesPerAddedTarget());
    }

    public function testAManagerPaysOneQueryPerTarget(): void
    {
        $this->loginAs(User::AUTH_ROLE_MANAGER);

        self::assertLessThanOrEqual(1, $this->countQueriesPerAddedTarget());
    }

    public function testATargetIsLookedUpOnce(): void
    {
        $this->loginAs(User::AUTH_ROLE_MANAGER);

        $webuser = $this->getWebUser();
        $target = $this->createUser(User::AUTH_ROLE_MANAGER);
        $copy = User::findOne($target->id) ?? self::fail();

        $webuser->can(User::AUTH_USER, ['user' => $target]);

        self::assertSame(0, $this->countQueries(function () use ($webuser, $target, $copy): void {
            $webuser->can(User::AUTH_USER, ['user' => $target]);
            $webuser->can(User::AUTH_USER_ASSIGN, ['user' => $copy]);
        }));
    }

    private function countQueriesPerAddedTarget(): int
    {
        $webuser = $this->getWebUser();

        // An assignment drops the manager's cache, so every target exists before the first check warms it.
        $warmUp = $this->createUser(User::AUTH_ROLE_MANAGER);
        $one = [$this->createUser(User::AUTH_ROLE_MANAGER)];
        $five = array_map(fn (): User => $this->createUser(User::AUTH_ROLE_MANAGER), range(1, 5));

        $webuser->can(User::AUTH_USER, ['user' => $warmUp]);

        $count = fn (array $targets): int => $this->countQueries(function () use ($webuser, $targets): void {
            foreach ($targets as $target) {
                $webuser->can(User::AUTH_USER, ['user' => $target]);
            }
        });

        return intdiv($count($five) - $count($one), 4);
    }

    private function createUser(string $role): User
    {
        static $count = 0;
        ++$count;

        $user = User::create();
        $user->loadDefaultValues();
        $user->status = User::STATUS_ENABLED;
        $user->name = "target$count";
        $user->email = "target$count@example.com";
        $user->language = 'en-US';

        self::assertTrue($user->insert(false), print_r($user->getErrors(), true));

        $this->assignRole($user->id, $role);

        return $user;
    }

    private function loginAs(string $role): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignRole($user->id, $role);

        $this->getWebUser()->disableRbacForOwner = false;
        $this->getWebUser()->setIdentity($user);
    }
}
