<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;
use yii\web\Cookie;

class ColorSchemeTest extends TestCase
{
    use UserFixtureTrait;

    /**
     * @var array<string, mixed>
     */
    private array $cookies = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->cookies = $_COOKIE;
    }

    #[Override]
    protected function tearDown(): void
    {
        // `$_COOKIE` is process-wide and outlives the application the test built.
        $_COOKIE = $this->cookies;
        parent::tearDown();
    }

    public function testWithoutACookieOrAnAccountTheBrowserDecides(): void
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

    public function testTheCookieBeatsTheAccountColumn(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->color_scheme = User::COLOR_SCHEME_DARK;

        $this->getWebUser()->setIdentity($user);
        $_COOKIE['_theme'] = User::COLOR_SCHEME_LIGHT;

        self::assertSame(User::COLOR_SCHEME_LIGHT, Module::current()->getColorScheme());
    }

    public function testAnUnknownCookieValueFallsThrough(): void
    {
        $_COOKIE['_theme'] = 'sepia';

        self::assertNull(Module::current()->getColorScheme());
    }

    public function testAColumnHoldingAValueNoLongerOfferedFallsThrough(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->color_scheme = 'sepia';

        $this->getWebUser()->setIdentity($user);

        self::assertNull(Module::current()->getColorScheme());
    }

    /**
     * The cookie is written by a script and therefore carries no signature, which
     * {@see \yii\web\Request::loadCookies()} silently skips while `enableCookieValidation` is on — so reading it
     * through {@see \Hirtz\Skeleton\Web\Request::getCookies()} would answer nothing at all, with no error to say
     * so. Both halves are asserted, or a tidy-up onto the filtered API would look green.
     */
    public function testTheCookieIsReadPastYiisCookieValidation(): void
    {
        $_COOKIE['_theme'] = User::COLOR_SCHEME_DARK;

        self::assertTrue($this->getWebRequest()->enableCookieValidation);
        self::assertFalse($this->getWebRequest()->getCookies()->has('_theme'));
        self::assertSame(User::COLOR_SCHEME_DARK, Module::current()->getCookieColorScheme());
    }

    /**
     * A `Domain` on the container's cookie is what leaves the host-only twin `$_COOKIE` then hides behind
     * (monorepo issue #195), so this one is built with `new` and must not pick one up.
     */
    public function testTheCookieIsHostOnlyEvenWithAConfiguredCookieDomain(): void
    {
        Yii::$container->set(Cookie::class, ['domain' => '.test.localhost']);

        self::assertSame('', Module::current()->getColorSchemeCookie()->domain);
    }

    public function testTheSecureFlagCanBePinned(): void
    {
        $module = Module::current();
        $module->colorSchemeCookieSecure = true;

        self::assertTrue($module->getColorSchemeCookie()->secure);

        $module->colorSchemeCookieSecure = false;

        self::assertFalse($module->getColorSchemeCookie()->secure);
    }
}
