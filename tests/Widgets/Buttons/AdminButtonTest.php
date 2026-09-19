<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Buttons;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Buttons\AdminButton;

class AdminButtonTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * The button is furniture for the admin overlay, and a project rendering the cms `site/view.php` unchanged
     * supplied no guard of its own — so every public page carried it, environment badge included (monorepo
     * issue #198).
     */
    public function testAGuestSeesNoButton(): void
    {
        self::assertSame('', AdminButton::make()->render());
    }

    public function testAnAuthenticatedAccountSeesTheButton(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        self::assertStringContainsString('class="admin-btn"', AdminButton::make()->render());
    }

    /**
     * The default is assigned after the event and the `prepare()` closures, so a caller narrowing the button to a
     * permission narrows it rather than widening it back to every account — `roles()` merges.
     */
    public function testACallersRolesReplaceTheDefault(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $button = AdminButton::make()->roles([User::AUTH_USER]);

        self::assertSame('', $button->render());
        self::assertSame([User::AUTH_USER], $button->getRoles());
    }
}
