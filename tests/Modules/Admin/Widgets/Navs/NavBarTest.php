<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBar;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class NavBarTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheNavbarRendersTheSearch(): void
    {
        $this->login();

        self::assertStringContainsString('class="navbar-search"', NavBar::make()->render());
    }

    public function testAGuestGetsNoSearch(): void
    {
        self::assertStringNotContainsString('navbar-search', NavBar::make()->render());
    }

    /**
     * The navbar survives every htmx swap while the id counter restarts on each request, so a generated id here
     * would sooner or later shadow the element of the same id in a later `#wrap`.
     */
    public function testTheNavbarCarriesNoGeneratedId(): void
    {
        $this->login();

        self::assertDoesNotMatchRegularExpression('/id="i\d+"/', NavBar::make()->render());
    }

    private function login(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
    }
}
