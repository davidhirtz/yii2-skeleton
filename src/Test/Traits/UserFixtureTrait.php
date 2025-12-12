<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use Yii;

trait UserFixtureTrait
{
    #[Override]
    public function fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    protected function getUserFixture(): UserFixture
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');
        return $fixture;
    }

    protected function getUserFromFixture(string $key): User
    {
        $fixture = $this->getUserFixture();
        return User::findOne($fixture->data[$key]['id']);
    }

    protected function assignAdminRole(int $userId): void
    {
        $this->assignRole($userId, User::AUTH_ROLE_ADMIN);
    }

    protected function assignPermission(int $userId, string $permission): void
    {
        $permission = Yii::$app->getAuthManager()->getPermission($permission);
        Yii::$app->getAuthManager()->assign($permission, $userId);
    }

    protected function assignRole(int $userId, string $role): void
    {
        $role = Yii::$app->getAuthManager()->getRole($role);
        Yii::$app->getAuthManager()->assign($role, $userId);
    }
}
