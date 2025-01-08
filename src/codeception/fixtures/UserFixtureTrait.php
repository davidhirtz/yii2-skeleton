<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\codeception\fixtures;

use davidhirtz\yii2\skeleton\models\User;
use Yii;

trait UserFixtureTrait
{
    public function _fixtures(): array
    {
        return $this->getUserFixture();
    }

    protected function getUserFixture(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'users.php',
            ],
        ];
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
