<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class ColorSchemeTest extends TestCase
{
    use UserFixtureTrait;

    public function testWithoutAnAccountTheBrowserDecides(): void
    {
        self::assertNull(Module::current()->getColorScheme());
    }

    public function testTheAccountColumnAnswersForALoggedInUser(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->color_scheme = User::COLOR_SCHEME_DARK;

        $this->getWebUser()->setIdentity($user);

        self::assertSame(User::COLOR_SCHEME_DARK, Module::current()->getColorScheme());
    }

    public function testAColumnHoldingAValueNoLongerOfferedFallsThrough(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->color_scheme = 'sepia';

        $this->getWebUser()->setIdentity($user);

        self::assertNull(Module::current()->getColorScheme());
    }
}
