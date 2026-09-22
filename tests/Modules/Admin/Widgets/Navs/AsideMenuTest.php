<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Navs;

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

    /**
     * The skeleton's aside opens straight into its menu, the header being the extension point a theme with a
     * logo of its own overrides.
     */
    public function testTheAsideRendersNoHeader(): void
    {
        $this->login();
        self::assertStringNotContainsString('aside-header', AsideMenu::make()->render());
    }

    /**
     * The control that collapses the aside is a navbar button, so nothing in the menu carries it — the aside
     * renders after the navbar and could not have been asked whether to draw one.
     */
    public function testTheAsideCarriesNoPinButton(): void
    {
        $this->login();
        self::assertStringNotContainsString('data-aside-pin', AsideMenu::make()->render());
    }

    /**
     * The logout posts from the modal's own button, so the nav link carries no `hx-post` a misclick could fire.
     */
    public function testTheLogoutAsksForConfirmation(): void
    {
        $this->login();

        $aside = AsideMenu::make()->render();

        preg_match('~<button[^>]*class="nav-link nav-logout-link"[^>]*>~', $aside, $trigger);
        $link = $trigger[0] ?? self::fail('The aside renders no logout link.');

        self::assertStringContainsString('data-modal="#', $link);
        self::assertStringNotContainsString('hx-post', $link);
        self::assertMatchesRegularExpression(
            '~<dialog[^>]*class="modal">.*?hx-post="[^"]*/admin/account/logout".*?</dialog>~s',
            $aside,
        );
    }

    private function login(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
    }
}
