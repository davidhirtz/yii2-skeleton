<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Override;
use RuntimeException;
use Yii;

trait UserFixtureTrait
{
    /**
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
    protected function getUserFixtureData(string $key): array
    {
        return $this->getUserFixture()->data[$key];
    }

    protected function getUserFromFixture(string $key): User
    {
        return User::findOne($this->getUserFixtureData($key)['id'])
            ?? throw new RuntimeException("User fixture \"$key\" was not loaded.");
    }

    protected function assignAdminRole(int $userId): void
    {
        $this->assignRole($userId, User::AUTH_ROLE_ADMIN);
    }

    protected function assignManagerRole(int $userId): void
    {
        $this->assignRole($userId, User::AUTH_ROLE_MANAGER);
    }

    protected function assignPermission(int $userId, string $permission): void
    {
        $auth = Yii::$app->getAuthManager();
        $item = $auth->getPermission($permission)
            ?? throw new RuntimeException("Permission \"$permission\" does not exist.");

        $auth->assign($item, $userId);
    }

    protected function assignRole(int $userId, string $role): void
    {
        $auth = Yii::$app->getAuthManager();
        $item = $auth->getRole($role)
            ?? throw new RuntimeException("Role \"$role\" does not exist.");

        $auth->assign($item, $userId);
    }
}
