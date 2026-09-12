<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\filters\AccessControl;

class DashboardControllerTest extends TestCase
{
    public function testDefaultRoles(): void
    {
        $roles = $this->getAccessRoles();

        self::assertContains(User::AUTH_USER_CREATE, $roles);
        self::assertContains(User::AUTH_USER_ASSIGN, $roles);
    }

    public function testAddRoles(): void
    {
        $roles = $this->getAccessRoles();
        self::assertNotContains('test1', $roles);

        DashboardController::addRoles(['test1']);
        DashboardController::addRoles(['test2']);

        self::assertSame([...$roles, 'test1', 'test2'], $this->getAccessRoles());
    }

    /**
     * @return string[]
     */
    private function getAccessRoles(): array
    {
        $controller = Yii::$app->getModule('admin')->createControllerByID('dashboard');
        self::assertInstanceOf(DashboardController::class, $controller);

        $access = $controller->getBehavior('access');
        self::assertInstanceOf(AccessControl::class, $access);

        return $access->rules[0]->roles;
    }
}
