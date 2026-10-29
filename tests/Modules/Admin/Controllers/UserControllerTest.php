<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

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

    public function testChangingTheOwnPasswordKeepsTheSession(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->login($owner);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->getWebRequest()->setBodyParams([
            'UserForm' => ['newPassword' => 'Neues-Passwort-ü-1', 'repeatPassword' => 'Neues-Passwort-ü-1'],
        ]);

        $this->createUserController()->actionUpdate($owner->id);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));

        $authKey = User::findOne($owner->id)?->auth_key;
        self::assertNotSame($owner->auth_key, $authKey);
        self::assertSame($authKey, $this->getWebSession()->get($this->getWebUser()->authKeyParam));
    }

    public function testStatusCyclesTheUserThroughItsStatuses(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        self::assertSame(User::STATUS_DISABLED, $user->status);

        $this->createUserController()->actionStatus($user->id);
        self::assertSame(User::STATUS_ENABLED, User::findOne($user->id)->status);

        self::assertSame([Yii::t('skeleton', 'MODEL_STATUS_UPDATE_SUCCESS', [
            'name' => $user->getAdminName(),
            'status' => Yii::t('skeleton', 'STATUS_ENABLED_NAME'),
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

    public function testNobodyDisablesTheirOwnAccount(): void
    {
        $user = $this->login();

        $html = $this->createUserController()->actionUpdate($user->id);

        self::assertIsString($html);
        self::assertStringNotContainsString('name="UserForm[status]"', $html);

        $this->expectException(NotFoundHttpException::class);
        $this->createUserController()->actionStatus($user->id);
    }

    public function testAUserTheActorMayNotManageOffersNeitherHistoryNorStatus(): void
    {
        $actor = $this->login();
        $this->assignPermission($actor->id, Trail::AUTH_TRAIL_INDEX);
        $this->getWebUser()->setIdentity($actor);

        $user = $this->getUserFromFixture('disabled');
        $this->assignAdminRole($user->id);

        $html = $this->createUserController()->actionUpdate($user->id);

        self::assertIsString($html);
        self::assertStringNotContainsString('/admin/user-trail/index?id=' . $user->id, $html);

        $html = $this->createUserController()->actionIndex();

        self::assertIsString($html);
        self::assertStringNotContainsString('/admin/user/status?id=' . $user->id . '"', $html);
        self::assertStringNotContainsString('/admin/user-login/view?user=' . $user->id . '"', $html);
    }

    public function testAFailedOwnershipTransferRedirectsWithTheError(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $this->assignPermission($owner->id, User::AUTH_USER);
        $this->getWebUser()->setIdentity($owner);

        $user = $this->getUserFromFixture('disabled');
        $response = $this->createUserController()->actionOwnership($user->id);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame([['name' => Yii::t('skeleton', 'USER_OWNER_DISABLED_ERROR')]], $this->getWebSession()->getFlash('danger'));
        self::assertFalse(User::findOne($user->id)->isOwner());
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
