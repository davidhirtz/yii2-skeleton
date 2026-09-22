<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsidePinButton;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AsidePinButtonTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$container->clear(AsidePinButton::class);
        unset($_COOKIE[$this->getAdminModule()->asideCookieName]);
    }

    #[Override]
    protected function tearDown(): void
    {
        unset($_COOKIE[$this->getAdminModule()->asideCookieName]);
        parent::tearDown();
    }

    public function testThePinButtonReportsTheExpandedAside(): void
    {
        $this->login();

        $button = AsidePinButton::make()->render();

        self::assertStringContainsString('data-aside-pin', $button);
        self::assertStringContainsString('aria-pressed="true"', $button);
        self::assertStringContainsString('fa-thumbtack"', $button);
        self::assertStringContainsString('aria-label="' . Yii::t('skeleton', 'ASIDE_UNPIN') . '"', $button);
    }

    /**
     * The cookie is written by `includes/aside.ts` and read straight out of `$_COOKIE`, the layout rendering
     * the attribute the CSS keys on — so a collapsed aside never unfolds for a frame on a full load.
     */
    public function testTheCookieCollapsesThePinButton(): void
    {
        $this->login();
        $_COOKIE[$this->getAdminModule()->asideCookieName] = Module::ASIDE_COLLAPSED;

        $button = AsidePinButton::make()->render();

        self::assertTrue($this->getAdminModule()->isAsideCollapsed());
        self::assertStringContainsString('aria-pressed="false"', $button);
        self::assertStringContainsString('fa-thumbtack-slash', $button);
        self::assertStringContainsString('aria-label="' . Yii::t('skeleton', 'ASIDE_PIN') . '"', $button);
    }

    public function testAnUnknownCookieValueLeavesTheAsideExpanded(): void
    {
        $_COOKIE[$this->getAdminModule()->asideCookieName] = '1';

        self::assertFalse($this->getAdminModule()->isAsideCollapsed());
    }

    /**
     * The navbar renders before the aside and cannot ask whether one came to anything, so both aside buttons
     * answer the guest check it reaches for everything shipped (monorepo issue #190).
     */
    public function testAGuestGetsNoPinButton(): void
    {
        self::assertSame('', AsidePinButton::make()->render());
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
