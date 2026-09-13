<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class UserLoginControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testIndexListsEveryLogin(): void
    {
        $this->login();

        $this->createUserLogin($this->getUserFromFixture('owner'), '10.0.0.1');
        $this->createUserLogin($this->getUserFromFixture('disabled'), '10.0.0.2');

        $html = Yii::$app->runAction('admin/user-login/index');

        self::assertIsString($html);
        self::assertStringContainsString('10.0.0.1', $html);
        self::assertStringContainsString('10.0.0.2', $html);
    }

    public function testIndexFiltersByIpAddress(): void
    {
        $this->login();

        $this->createUserLogin($this->getUserFromFixture('owner'), '10.0.0.1');
        $this->createUserLogin($this->getUserFromFixture('disabled'), '10.0.0.2');

        $html = Yii::$app->runAction('admin/user-login/index', ['q' => '10.0.0.1']);

        self::assertIsString($html);
        self::assertStringContainsString('10.0.0.1', $html);
        self::assertStringNotContainsString('10.0.0.2', $html);
    }

    /**
     * `inet_pton()` answers `false` for anything that is not an address, and the filter must not fall back to
     * listing everything.
     */
    public function testIndexWithAnInvalidIpAddressListsNothing(): void
    {
        $this->login();
        $this->createUserLogin($this->getUserFromFixture('owner'), '10.0.0.1');

        $html = Yii::$app->runAction('admin/user-login/index', ['q' => 'not-an-ip']);

        self::assertIsString($html);
        self::assertStringNotContainsString('10.0.0.1', $html);
    }

    public function testIndexLinksAnIpAddressBackToItsOwnFilter(): void
    {
        $this->login();
        $this->createUserLogin($this->getUserFromFixture('owner'), '10.0.0.1');

        $html = Yii::$app->runAction('admin/user-login/index');

        self::assertIsString($html);
        self::assertStringContainsString('q=10.0.0.1', $html);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-login/index');
    }

    public function testViewListsOnlyTheLoginsOfThatUser(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->createUserLogin($user, '10.0.0.1');
        $this->createUserLogin($this->getUserFromFixture('owner'), '10.0.0.2');

        $html = Yii::$app->runAction('admin/user-login/view', ['user' => $user->id]);

        self::assertIsString($html);
        self::assertStringContainsString('10.0.0.1', $html);
        self::assertStringNotContainsString('10.0.0.2', $html);
    }

    public function testViewOfAnUnknownUserIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/user-login/view', ['user' => 99999]);
    }

    public function testViewOfAUserTheActorMayNotManageIsForbidden(): void
    {
        $this->login();

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-login/view', ['user' => $this->getUserFromFixture('owner')->id]);
    }

    private function createUserLogin(User $user, string $ip): UserLogin
    {
        $login = UserLogin::create();
        $login->user_id = $user->id;
        $login->type = UserLogin::TYPE_LOGIN;
        $login->ip_address = inet_pton($ip);

        // the row is written raw by `Web\User::insertLogin()`, so nothing stamps it
        $login->created_at = new DateTime();

        self::assertTrue($login->insert());

        return $login;
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
