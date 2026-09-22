<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\NavBar;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class NavBarTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$container->clear(NavBar::class);
    }

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
     * Every item is guarded, so a logged out view was left with an empty bar carrying a toggle for an aside that
     * had already hidden itself (monorepo issue #190) — a bar holding nothing renders nothing at all. The one
     * item a guest can still get is the language picker, which needs a second language to render.
     */
    public function testAGuestGetsNoNavbarAtAll(): void
    {
        self::assertSame('', NavBar::make()->render());
    }

    /**
     * The login page is where the admin language is first picked, so a guest gets the picker too.
     */
    public function testAGuestPicksALanguageWithoutGettingTheAsideToggle(): void
    {
        $this->getAdminModule()->languages = ['en-US', 'de'];
        Yii::$app->runAction('admin/account/login');

        $navbar = NavBar::make()->render();

        self::assertStringContainsString('/admin/account/language', $navbar);
        self::assertStringNotContainsString('aside-toggle', $navbar);
        self::assertStringNotContainsString('aside-pin', $navbar);
    }

    public function testTheLoggedInNavbarKeepsItsToggle(): void
    {
        $this->login();

        self::assertStringContainsString('aside-toggle', NavBar::make()->render());
    }

    /**
     * The drawer toggle and the pin share a slot, the breakpoint deciding which one is drawn — so both are in
     * the markup and neither is the other's fallback.
     */
    public function testTheLoggedInNavbarCarriesThePinBesideTheToggle(): void
    {
        $this->login();

        $navbar = NavBar::make()->render();

        self::assertStringContainsString('aside-pin', $navbar);
        self::assertStringContainsString('data-aside-pin', $navbar);
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

    private function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}
