<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\rbac\Item;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UserAuthControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testIndexMarksTheAssignedItems(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);

        $html = Yii::$app->runAction('admin/user-auth/index', ['id' => $user->id]);

        self::assertIsString($html);
        self::assertStringContainsString(User::AUTH_USER_ASSIGN, $html);

        // the assigned item offers the revoke route, the unassigned ones the assign route
        self::assertStringContainsString('user-auth/delete', $html);
        self::assertStringContainsString('user-auth/create', $html);
    }

    public function testIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-auth/index', ['id' => $user->id]);
    }

    public function testIndexOfAnUnknownUserIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/user-auth/index', ['id' => 99999]);
    }

    public function testAUserHoldingAPermissionTheActorLacksIsForbidden(): void
    {
        $this->loginWithAuthUpdateOnly();
        $user = $this->getUserFromFixture('disabled');
        $this->assignPermission($user->id, User::AUTH_USER);

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-auth/index', ['id' => $user->id]);
    }

    public function testTheOwnerIsForbidden(): void
    {
        $this->login();
        $owner = $this->getUserFromFixture('owner');

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/user-auth/index', ['id' => $owner->id]);
    }

    public function testCreateAssignsThePermission(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $response = $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER,
            'type' => Item::TYPE_PERMISSION,
        ]);

        self::assertInstanceOf(Response::class, $response);
        self::assertArrayHasKey(User::AUTH_USER, Yii::$app->getAuthManager()->getPermissionsByUser($user->id));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    /**
     * `Rbac\DbManager::assign()` hands back the assignment that is already there rather than failing, so a repeated
     * assignment is idempotent: no second row, no second trail, and the controller's "already assigned" branch is
     * never reached.
     */
    public function testAssigningTwiceIsIdempotent(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);

        $trails = Trail::find()->count();

        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER_ASSIGN,
            'type' => Item::TYPE_PERMISSION,
        ]);

        self::assertCount(1, Yii::$app->getAuthManager()->getPermissionsByUser($user->id));
        self::assertEquals($trails, Trail::find()->count());
    }

    public function testCreateAssignsARole(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_ROLE_ADMIN,
            'type' => Item::TYPE_ROLE,
        ]);

        self::assertArrayHasKey(User::AUTH_ROLE_ADMIN, Yii::$app->getAuthManager()->getRolesByUser($user->id));
    }

    /**
     * `authUpdate` alone would otherwise be enough to hand the `admin` role to an account of one's own and take
     * the installation over.
     */
    public function testCreateRefusesAnItemTheActorDoesNotHold(): void
    {
        $this->loginWithAuthUpdateOnly();
        $user = $this->getUserFromFixture('disabled');

        try {
            $this->post('admin/user-auth/create', [
                'id' => $user->id,
                'name' => User::AUTH_ROLE_ADMIN,
                'type' => Item::TYPE_ROLE,
            ]);

            self::fail('The assignment was not refused.');
        } catch (ForbiddenHttpException) {
            self::assertEmpty(Yii::$app->getAuthManager()->getRolesByUser($user->id));
        }
    }

    public function testCreateAssignsAnItemTheActorHolds(): void
    {
        $this->loginWithAuthUpdateOnly();
        $user = $this->getUserFromFixture('disabled');

        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER_ASSIGN,
            'type' => Item::TYPE_PERMISSION,
        ]);

        self::assertArrayHasKey(
            User::AUTH_USER_ASSIGN,
            Yii::$app->getAuthManager()->getPermissionsByUser($user->id)
        );
    }

    public function testDeleteRevokesThePermission(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);

        $this->post('admin/user-auth/delete', [
            'id' => $user->id,
            'name' => User::AUTH_USER_ASSIGN,
            'type' => Item::TYPE_PERMISSION,
        ]);

        self::assertEmpty(Yii::$app->getAuthManager()->getPermissionsByUser($user->id));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('success'));
    }

    public function testDeleteReportsAnItemThatWasNotAssigned(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->post('admin/user-auth/delete', [
            'id' => $user->id,
            'name' => User::AUTH_USER_ASSIGN,
            'type' => Item::TYPE_PERMISSION,
        ]);

        self::assertEmpty(Yii::$app->getSession()->getFlash('success'));
        self::assertNotEmpty(Yii::$app->getSession()->getFlash('danger'));
    }

    public function testAnUnknownItemIsNotFound(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(NotFoundHttpException::class);

        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => 'doesNotExist',
            'type' => Item::TYPE_PERMISSION,
        ]);
    }

    public function testAnItemOfTheWrongTypeIsNotFound(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(NotFoundHttpException::class);

        // the permission exists, but is asked for as a role
        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER,
            'type' => Item::TYPE_ROLE,
        ]);
    }

    public function testAnUnknownTypeIsNotFound(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(NotFoundHttpException::class);

        $this->post('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER,
            'type' => 99,
        ]);
    }

    public function testCreateRefusesAGetRequest(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(MethodNotAllowedHttpException::class);

        Yii::$app->runAction('admin/user-auth/create', [
            'id' => $user->id,
            'name' => User::AUTH_USER,
            'type' => Item::TYPE_PERMISSION,
        ]);
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();
        $user = $this->getUserFromFixture('disabled');

        $this->expectException(MethodNotAllowedHttpException::class);

        Yii::$app->runAction('admin/user-auth/delete', [
            'id' => $user->id,
            'name' => User::AUTH_USER,
            'type' => Item::TYPE_PERMISSION,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    /**
     * An administrator holds every permission, so they may hand out any of them.
     */
    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }

    /**
     * The actor holds `authUpdate` alone, so `Web\User::canManageUser()` has to answer for every target and
     * every item they hand out has to be one they hold themselves.
     */
    private function loginWithAuthUpdateOnly(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
