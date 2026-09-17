<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBarSearch;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class NavBarSearchTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheSearchOverridesTheInheritedHtmxAttributes(): void
    {
        $this->login();
        $html = NavBarSearch::make()->render();

        self::assertStringContainsString('class="navbar-search"', $html);
        self::assertStringContainsString('hx-select="#' . SearchController::LIST_ID . '"', $html);
        self::assertStringContainsString('hx-select-oob=""', $html);
        self::assertStringContainsString('hx-swap="innerHTML"', $html);
        self::assertStringContainsString('hx-target="#' . NavBarSearch::RESULTS_ID . '"', $html);
    }

    public function testTheToggleCarriesBothIcons(): void
    {
        $this->login();
        $html = NavBarSearch::make()->render();

        self::assertStringContainsString('aria-expanded="false"', $html);
        self::assertStringContainsString('navbar-search-toggle-open fas fa-search', $html);
        self::assertStringContainsString('navbar-search-toggle-close fas fa-xmark', $html);
    }

    public function testAGuestGetsNoSearch(): void
    {
        self::assertSame('', NavBarSearch::make()->render());
    }

    public function testTheDisabledModuleGetsNoSearch(): void
    {
        $this->login();

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->enableSearch = false;

        self::assertSame('', NavBarSearch::make()->render());
    }

    private function login(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
    }
}
