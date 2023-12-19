<?php

namespace davidhirtz\yii2\skeleton\tests\functional;

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

    public function _after(FunctionalTester $I): void
    {
        FileHelper::removeDirectory($this->getAssetPath());
    }

    protected function getAssetPath(): string
    {
        return Yii::getAlias('@runtime/assets');
    }

    protected function assignRole(int $userId, string $role = User::AUTH_ROLE_ADMIN): void
    {
        $role = Yii::$app->getAuthManager()->getRole($role);
        Yii::$app->getAuthManager()->assign($role, $userId);
    }
}
