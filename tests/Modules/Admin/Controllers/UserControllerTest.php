<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testUpdateRendersAUserItMayNotChangeReadOnly(): void
    {
        $this->login();
        $owner = $this->getUserFromFixture('owner');

        $html = $this->createUserController()->actionUpdate($owner->id);

        self::assertIsString($html);
        self::assertStringContainsString('<fieldset class="fieldset" disabled>', $html);
        self::assertStringNotContainsString('type="submit"', $html);
    }

    public function testUpdateRendersAnEditableUserWithItsButtons(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $html = $this->createUserController()->actionUpdate($user->id);

        self::assertIsString($html);
        self::assertStringNotContainsString('<fieldset class="fieldset" disabled>', $html);
        self::assertStringContainsString('type="submit"', $html);
    }

    public function testUpdateDoesNotSaveAUserItMayNotChange(): void
    {
        $this->login();
        $owner = $this->getUserFromFixture('owner');

        Yii::$app->getRequest()->setBodyParams([
            'UserForm' => ['email' => 'taken@domain.com'],
        ]);

        self::assertIsString($this->createUserController()->actionUpdate($owner->id));
        self::assertSame($owner->email, User::findOne($owner->id)->email);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }

    private function createUserController(): UserController
    {
        $controller = Yii::$app->getModule('admin')->createControllerByID('user');
        self::assertInstanceOf(UserController::class, $controller);

        // The delete button's route is relative, and nothing resolves one without an active controller
        Yii::$app->controller = $controller;

        return $controller;
    }
}
