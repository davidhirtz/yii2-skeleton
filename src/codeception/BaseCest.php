<?php

namespace davidhirtz\yii2\skeleton\codeception;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use Yii;

abstract class BaseCest
{
    public function _before(FunctionalTester $I): void
    {
        $path = $this->getAssetPath();
        FileHelper::createDirectory($path);

        Yii::$app->getAssetManager()->basePath = $path;
    }

    /**
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function _after(FunctionalTester $I): void
    {
        Yii::$app->getUser()->logout();
        FileHelper::removeDirectory($this->getAssetPath());
    }

    protected function getAssetPath(): string
    {
        return Yii::getAlias('@runtime/assets');
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
