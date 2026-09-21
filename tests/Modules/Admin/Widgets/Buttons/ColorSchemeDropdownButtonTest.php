<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\ColorSchemeDropdownButton;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class ColorSchemeDropdownButtonTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheDropdownOffersAllThreeStates(): void
    {
        $content = ColorSchemeDropdownButton::make()->render();

        self::assertStringContainsString('data-color-scheme-value=""', $content);
        self::assertStringContainsString('data-color-scheme-value="light"', $content);
        self::assertStringContainsString('data-color-scheme-value="dark"', $content);
    }

    public function testTheCurrentSchemeIsTheSelectedOption(): void
    {
        $_COOKIE['_theme'] = User::COLOR_SCHEME_DARK;

        self::assertStringContainsString(
            'class="dropdown-option selected" data-color-scheme-value="dark"',
            ColorSchemeDropdownButton::make()->render(),
        );
    }

    public function testWithoutASchemeTheAutoOptionIsSelected(): void
    {
        self::assertStringContainsString(
            'class="dropdown-option selected" data-color-scheme-value=""',
            ColorSchemeDropdownButton::make()->render(),
        );
    }

    /**
     * The route is what tells the script there is a column to persist to, so a guest must not get one — and the
     * action is behind `@` either way.
     */
    public function testAGuestGetsNoRouteToPersistTo(): void
    {
        self::assertStringNotContainsString('data-color-scheme-url', ColorSchemeDropdownButton::make()->render());
    }

    public function testAnAccountGetsTheRouteToPersistTo(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        self::assertStringContainsString(
            'data-color-scheme-url="/admin/account/color-scheme"',
            ColorSchemeDropdownButton::make()->render(),
        );
    }

    /**
     * The navbar survives every htmx swap while the id counter restarts per request, so a generated id here
     * would sooner or later shadow the element of the same id in a later `#wrap`.
     */
    public function testThePopoverIdIsALiteral(): void
    {
        $content = ColorSchemeDropdownButton::make()->render();

        self::assertStringContainsString('id="color-scheme"', $content);
        self::assertDoesNotMatchRegularExpression('/id="i\d+"/', $content);
    }
}
