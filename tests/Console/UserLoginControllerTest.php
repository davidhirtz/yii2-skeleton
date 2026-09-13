<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\UserLoginController;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\base\InvalidConfigException;

class UserLoginControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testActionClearWithoutLifetime(): void
    {
        $controller = $this->createController();

        $this->expectException(InvalidConfigException::class);
        $controller->actionClear();
    }

    public function testActionClear(): void
    {
        $userId = $this->getUserFixtureData('owner')['id'];

        $this->insertLogin($userId, '-2 years');
        $this->insertLogin($userId, '-1 hour');

        $controller = $this->createController();
        $controller->sleep = 0;

        $twoYearsAndADay = 60 * 60 * 24 * 731;
        $controller->actionClear($twoYearsAndADay);

        self::assertEquals('No expired login records found' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertEquals(2, UserLogin::find()->count());

        $controller->actionClear(60 * 60 * 24);

        self::assertStringContainsString('Deleted 1 expired login records', $controller->flushStdOutBuffer());
        self::assertEquals(1, UserLogin::find()->count());
    }

    public function testLifetimeComesFromTheAdminModule(): void
    {
        Yii::$app->getModule('admin')->userLoginLifetime = 60;

        $this->insertLogin($this->getUserFixtureData('owner')['id'], '-1 day');

        $controller = $this->createController();
        $controller->sleep = 0;
        $controller->actionClear();

        self::assertStringContainsString('Deleted 1 expired login records', $controller->flushStdOutBuffer());
        self::assertEquals(0, UserLogin::find()->count());
    }

    private function insertLogin(int|string $userId, string $modify): void
    {
        Yii::$app->getDb()->createCommand()
            ->insert(UserLogin::tableName(), [
                'user_id' => $userId,
                'type' => UserLogin::TYPE_LOGIN,
                'created_at' => gmdate('Y-m-d H:i:s', strtotime($modify)),
            ])
            ->execute();
    }

    private function createController(): UserLoginControllerMock
    {
        return new UserLoginControllerMock('user-login', Yii::$app);
    }
}

class UserLoginControllerMock extends UserLoginController
{
    use StdOutBufferControllerTrait;
}
