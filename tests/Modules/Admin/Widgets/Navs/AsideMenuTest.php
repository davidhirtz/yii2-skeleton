<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AsideMenuTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$container->clear(AsideMenu::class);
        unset($_COOKIE[$this->getAdminModule()->asideCookieName]);
    }

    #[Override]
    protected function tearDown(): void
    {
        unset($_COOKIE[$this->getAdminModule()->asideCookieName]);
        parent::tearDown();
    }

    public function testTheAsideRendersBothMenus(): void
    {
        $this->login();

        $aside = AsideMenu::make()->render();

        self::assertStringContainsString('class="aside-main aside-nav nav"', $aside);
        self::assertStringContainsString('id="account-menu"', $aside);
    }

    /**
     * A guest reaches no item at all, and the aside is left out of the document rather than hidden by a CSS rule
     * — which a theme rendering a logo of its own into it would defeat.
     */
    public function testAGuestGetsNoAside(): void
    {
        self::assertSame('', AsideMenu::make()->render());
    }

    public function testThePinButtonReportsTheExpandedAside(): void
    {
        $this->login();

        $aside = AsideMenu::make()->render();

        self::assertStringContainsString('class="aside-header"', $aside);
        self::assertStringContainsString('data-aside-pin', $aside);
        self::assertStringContainsString('aria-pressed="true"', $aside);
        self::assertStringContainsString('fa-thumbtack"', $aside);
    }

    /**
     * The cookie is written by `includes/asidePin.ts` and read straight out of `$_COOKIE`, the layout rendering
     * the attribute the CSS keys on — so a collapsed aside never unfolds for a frame on a full load.
     */
    public function testTheCookieCollapsesThePinButton(): void
    {
        $this->login();
        $_COOKIE[$this->getAdminModule()->asideCookieName] = Module::ASIDE_COLLAPSED;

        $aside = AsideMenu::make()->render();

        self::assertTrue($this->getAdminModule()->isAsideCollapsed());
        self::assertStringContainsString('aria-pressed="false"', $aside);
        self::assertStringContainsString('fa-thumbtack-slash', $aside);
    }

    public function testAnUnknownCookieValueLeavesTheAsideExpanded(): void
    {
        $_COOKIE[$this->getAdminModule()->asideCookieName] = '1';

        self::assertFalse($this->getAdminModule()->isAsideCollapsed());
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
