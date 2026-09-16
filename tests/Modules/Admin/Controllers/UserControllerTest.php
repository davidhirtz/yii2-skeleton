<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;

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

        $this->getWebRequest()->setBodyParams([
            'UserForm' => ['email' => 'taken@domain.com'],
        ]);

        self::assertIsString($this->createUserController()->actionUpdate($owner->id));
        self::assertSame($owner->email, User::findOne($owner->id)->email);
    }

    public function testStatusCyclesTheUserThroughItsStatuses(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        self::assertSame(User::STATUS_DISABLED, $user->status);

        $this->createUserController()->actionStatus($user->id);
        self::assertSame(User::STATUS_ENABLED, User::findOne($user->id)->status);

        self::assertSame([Yii::t('skeleton', 'COMMON_STATUS_SUCCESS_UPDATED', [
            'name' => $user->getAdminName(),
            'status' => Yii::t('skeleton', 'COMMON_ENABLED'),
        ])], $this->getWebSession()->getFlash('success'));

        // And back around, the list having only the two.
        $this->createUserController()->actionStatus($user->id);
        self::assertSame(User::STATUS_DISABLED, User::findOne($user->id)->status);
    }

    /**
     * Two guards stand in front of the owner's star, and the outer one answers first: `Web\User::canManageUser()`
     * refuses a target holding a permission the acting user lacks, before `isStatusUpdatable()` is ever asked.
     */
    public function testStatusRefusesTheSiteOwner(): void
    {
        $this->login();
        $owner = $this->getUserFromFixture('owner');

        $this->expectException(ForbiddenHttpException::class);
        $this->createUserController()->actionStatus($owner->id);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER);

        $this->getWebUser()->setIdentity($user);

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
