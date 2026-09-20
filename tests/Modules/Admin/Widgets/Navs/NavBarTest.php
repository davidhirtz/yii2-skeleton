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
     * Every other item is guarded, so a logged out view used to be left with an empty bar carrying a toggle for
     * an aside that had already hidden itself (monorepo issue #190). The colour scheme dropdown is the one item
     * a guest always has — the login page is where the scheme is first picked — so what #190 asks of the bar now
     * is that nothing *else* survives into it.
     */
    public function testAGuestGetsTheColorSchemeAndNothingElse(): void
    {
        $navbar = NavBar::make()->render();

        self::assertStringContainsString('data-color-scheme', $navbar);
        self::assertStringNotContainsString('navbar-search', $navbar);
        self::assertStringNotContainsString('aside-toggle', $navbar);
        self::assertStringNotContainsString('i18n-dropdown-option', $navbar);
    }

    /**
     * The login page is where the admin language is first picked, so a guest gets the picker too.
     */
    public function testAGuestPicksALanguageWithoutGettingTheAsideToggle(): void
    {
        $this->getAdminModule()->languages = ['en-US', 'de'];
        Yii::$app->runAction('admin/account/login');

        $navbar = NavBar::make()->render();

        self::assertStringContainsString('i18n-dropdown-option', $navbar);
        self::assertStringNotContainsString('aside-toggle', $navbar);
    }

    public function testTheLoggedInNavbarKeepsItsToggle(): void
    {
        $this->login();

        self::assertStringContainsString('aside-toggle', NavBar::make()->render());
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
