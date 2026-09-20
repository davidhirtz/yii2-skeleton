<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Symfony\Component\BrowserKit\Cookie;

class ColorSchemeTest extends TestCase
{
    use FunctionalTestTrait;
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
        $_COOKIE = $this->cookies;
        parent::tearDown();
    }

    /**
     * No attribute is the third state: nothing pinned, so `prefers-color-scheme` decides in the stylesheet. The
     * server cannot answer it — `Sec-CH-Prefers-Color-Scheme` is Chromium-only and needs an `Accept-CH` first.
     */
    public function testTheLoginPageRendersNoAttributeByDefault(): void
    {
        $this->open('admin/account/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('html[data-theme]');
    }

    /**
     * The login page is the reason the cookie exists rather than a session value: a guest has no column to read.
     * The cookie goes into the jar rather than into `$_COOKIE`, which {@see \Hirtz\Skeleton\Test\Browser}
     * replaces from the jar on every request anyway — so this is the whole path, browser included.
     */
    public function testAGuestPinsTheSchemeThroughTheCookie(): void
    {
        $this->open('admin/account/login');

        self::assertSelectorExists('[data-color-scheme]');
        self::assertSelectorNotExists('html[data-theme]');

        self::$client->getCookieJar()->set(new Cookie('_theme', User::COLOR_SCHEME_DARK));
        self::$crawler = self::$client->request('GET', 'https://www.test.localhost/admin/account/login');

        self::assertSelectorExists('html[data-theme="dark"]');
    }

    public function testTheAccountColumnIsRenderedIntoTheLayout(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->color_scheme = User::COLOR_SCHEME_DARK;
        $user->update();

        $this->login('owner');
        $this->open('admin/dashboard/index');

        self::assertSelectorExists('html[data-theme="dark"]');
    }

    public function testTheSchemeIsSavedToTheAccount(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->login('owner');
        $this->postColorScheme(User::COLOR_SCHEME_DARK);

        self::assertResponseStatusCodeSame(204);
        self::assertSame(User::COLOR_SCHEME_DARK, User::findOne($user->id)->color_scheme);
    }

    /**
     * The empty value is how the account goes back to following the browser, so it has to reach the column as
     * `null` rather than as an empty string.
     */
    public function testAnEmptySchemeClearsTheAccountColumn(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->color_scheme = User::COLOR_SCHEME_DARK;
        $user->update();

        $this->login('owner');
        $this->postColorScheme('');

        self::assertResponseStatusCodeSame(204);
        self::assertNull(User::findOne($user->id)->color_scheme);
    }

    public function testAnUnknownSchemeIsRefused(): void
    {
        $this->login('owner');
        $this->postColorScheme('sepia');

        self::assertResponseStatusCodeSame(400);
    }

    private function postColorScheme(string $colorScheme): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/color-scheme', [
            'colorScheme' => $colorScheme,
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    private function login(string $fixtureKey): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture($fixtureKey)->email,
            'password' => 'password',
        ]));
    }
}
