<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use ArrayObject;
use Exception;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Widgets\Widget;
use Yii;
use yii\base\Event;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class DashboardControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testPanelAnswersALazyPanelBehindTheDashboardsRoles(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->getWebUser()->setIdentity($user);

        $denial = null;

        try {
            Yii::$app->runAction('admin/dashboard/panel', ['name' => 'slow']);
        } catch (Exception $exception) {
            $denial = $exception;
        }

        self::assertInstanceOf(ForbiddenHttpException::class, $denial);

        // A fresh application, since `can()` remembers the denial; it also drops class-level handlers.
        $this->assignPermission($user->id, User::AUTH_USER);
        $this->reloadApplication();
        $this->getWebUser()->setIdentity($user);

        Event::on(Dashboard::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var Dashboard $dashboard */
            $dashboard = $event->sender;
            $dashboard->addPanel(slow: DashboardPanel::make()->lazy()->content('Slow body'));
        });

        self::assertStringContainsString('Slow body', (string)Yii::$app->runAction('admin/dashboard/panel', ['name' => 'slow']));
        self::assertSame('', Yii::$app->runAction('admin/dashboard/panel', ['name' => 'missing']));
    }

    public function testDefaultRoles(): void
    {
        $roles = $this->getAccessRoles();

        self::assertContains(User::AUTH_USER, $roles);
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

    public function testAddRolesFromClosureIsDeferredUntilTheControllerIsCreated(): void
    {
        $roles = $this->getAccessRoles();

        /** @var ArrayObject<int, string> $calls */
        $calls = new ArrayObject();

        DashboardController::addRoles(function () use ($calls): array {
            $calls[] = 'called';
            return ['test3'];
        });

        self::assertCount(0, $calls);
        self::assertSame([...$roles, 'test3'], $this->getAccessRoles());
        self::assertCount(1, $calls);
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
