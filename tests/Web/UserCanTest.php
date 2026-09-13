<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserCanTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheOwnerCannotBeManagedByAnotherUser(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $admin = $this->getUserFromFixture('admin');

        $this->assignAdminRole($admin->id);
        $this->loginAs($admin);

        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER, ['user' => $owner]));
        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER_ASSIGN, ['user' => $owner]));
    }

    public function testAnAdminCanManageAnOrdinaryUser(): void
    {
        $admin = $this->getUserFromFixture('admin');
        $other = $this->getUserFromFixture('disabled');

        $this->assignAdminRole($admin->id);
        $this->loginAs($admin);

        self::assertTrue(Yii::$app->getUser()->can(User::AUTH_USER, ['user' => $other]));
    }

    public function testAUserCannotManageSomeoneWithMorePermissions(): void
    {
        $editor = $this->getUserFromFixture('disabled');
        $admin = $this->getUserFromFixture('admin');

        // The editor may manage users, but holds nothing else the admin holds
        $this->assignPermission($editor->id, User::AUTH_USER);
        $this->assignAdminRole($admin->id);

        $this->loginAs($editor);

        self::assertTrue(Yii::$app->getUser()->can(User::AUTH_USER, ['user' => $editor]));
        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER, ['user' => $admin]));
    }

    public function testAnActorHoldingEverythingIsAnsweredWithoutALookup(): void
    {
        $admin = $this->getUserFromFixture('admin');
        $other = $this->getUserFromFixture('disabled');

        $this->assignAdminRole($admin->id);
        $this->loginAs($admin);

        $webuser = Yii::$app->getUser();
        $count = count(Yii::$app->getAuthManager()->getPermissions());

        self::assertCount($count, Yii::$app->getAuthManager()->getPermissionsByUser($admin->id));
        self::assertTrue($webuser->canManageUser($other));
    }

    public function testThePermissionIsStillCheckedForAManageableUser(): void
    {
        $editor = $this->getUserFromFixture('disabled');
        $other = $this->getUserFromFixture('admin');

        $this->loginAs($editor);

        self::assertTrue(Yii::$app->getUser()->canManageUser($other));
        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER, ['user' => $other]));
    }

    private function loginAs(User $user): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;
        Yii::$app->getUser()->setIdentity($user);
    }
}
