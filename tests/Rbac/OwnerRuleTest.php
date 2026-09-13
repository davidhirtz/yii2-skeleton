<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Rbac;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class OwnerRuleTest extends TestCase
{
    use UserFixtureTrait;

    public function testOwnerCannotBeUpdatedByAnotherUser(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $admin = $this->getUserFromFixture('admin');

        $this->assignAdminRole($admin->id);
        $this->loginAs($admin);

        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $owner]));
    }

    public function testAdminCanUpdateAnOrdinaryUser(): void
    {
        $admin = $this->getUserFromFixture('admin');
        $other = $this->getUserFromFixture('disabled');

        $this->assignAdminRole($admin->id);
        $this->loginAs($admin);

        self::assertTrue(Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $other]));
    }

    public function testUserCannotUpdateOrDeleteSomeoneWithMorePermissions(): void
    {
        $editor = $this->getUserFromFixture('disabled');
        $admin = $this->getUserFromFixture('admin');

        // The editor may manage users, but holds nothing else the admin holds
        $this->assignPermission($editor->id, User::AUTH_USER_UPDATE);
        $this->assignPermission($editor->id, User::AUTH_USER_DELETE);
        $this->assignAdminRole($admin->id);

        $this->loginAs($editor);

        self::assertTrue(Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $editor]));
        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER_UPDATE, ['user' => $admin]));
        self::assertFalse(Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $admin]));
    }

    private function loginAs(User $user): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;
        Yii::$app->getUser()->setIdentity($user);
    }
}
