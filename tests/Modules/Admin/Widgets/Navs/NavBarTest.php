<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBar;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class NavBarTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheSearchOverridesTheInheritedHtmxAttributes(): void
    {
        $this->login();
        $html = NavBar::make()->render();

        self::assertStringContainsString('class="navbar-search"', $html);
        self::assertStringContainsString('hx-select="#' . SearchController::LIST_ID . '"', $html);
        self::assertStringContainsString('hx-select-oob="unset"', $html);
        self::assertStringContainsString('hx-swap="innerHTML"', $html);
        self::assertStringContainsString('hx-target="#', $html);
    }

    public function testAGuestGetsNoSearch(): void
    {
        self::assertStringNotContainsString('navbar-search', NavBar::make()->render());
    }

    public function testTheDisabledModuleGetsNoSearch(): void
    {
        $this->login();

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->enableSearch = false;

        self::assertStringNotContainsString('navbar-search', NavBar::make()->render());
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }
}
