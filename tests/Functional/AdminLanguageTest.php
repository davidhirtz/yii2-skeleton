<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AdminLanguageTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testTheSessionLanguageOutlivesOnlyItsConfiguration(): void
    {
        $module = self::getAdminModule();
        $module->setSessionLanguage('de');

        self::assertSame('de', $module->getSessionLanguage());

        $module->languages = ['en-US', 'fr'];
        self::assertNull($module->getSessionLanguage());

        $module->languages = ['en-US', 'de'];
        $module->setSessionLanguage(null);

        self::assertNull($module->getSessionLanguage());
    }

    /**
     * The admin list is independent of the content languages, so a language the content is written in is not
     * automatically one the admin is offered in.
     */
    public function testALanguageOutsideTheAdminListIsRefused(): void
    {
        self::getAdminModule()->languages = ['en-US'];
        $this->login();

        $this->postLanguage('de');

        self::assertResponseStatusCodeSame(400);
        self::assertNull(self::getAdminModule()->getSessionLanguage());
    }

    /**
     * A single admin language leaves nothing to pick: it is pinned, and neither the dropdown nor the account's
     * language field renders.
     */
    public function testASingleLanguageIsPinnedAndHidesThePickers(): void
    {
        self::getAdminModule()->languages = ['de'];
        $this->login();

        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('de');
        self::assertSelectorNotExists('.i18n-dropdown-option');
        self::assertSelectorNotExists('select[name$="[language]"]');
    }

    public function testThePickedLanguageOutlivesTheRequestThatSetIt(): void
    {
        $this->login();

        $this->postLanguage('de');
        self::assertSame('de', self::getAdminModule()->getSessionLanguage());

        $this->open('admin');
        self::assertResponseIsSuccessful();
        self::assertLanguageSame('de');

        $this->postLanguage('en-US');

        $this->open('admin');
        self::assertLanguageSame('en');
    }

    /**
     * The navbar renders outside `#wrap`, so the page the flag was rendered with is not the page it is clicked
     * on — the whole document is reloaded rather than swapped, and the URL is the browser's own.
     */
    public function testAnHtmxRequestIsAnsweredWithARefresh(): void
    {
        $this->login();

        $this->postLanguage('de', ['HTTP_HX_REQUEST' => 'true']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertResponseNotHasHeader('hx-location');
        self::assertSame('de', self::getAdminModule()->getSessionLanguage());
    }

    public function testAPlainRequestRedirectsToTheReferrer(): void
    {
        $this->login();

        self::$client->followRedirects(false);
        $this->postLanguage('de', ['HTTP_REFERER' => 'https://www.test.localhost/admin/user/index']);

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', 'https://www.test.localhost/admin/user/index');
    }

    /**
     * A guest has no account to fall back to, and the login page is where the language is picked first.
     */
    public function testTheLoginPageSwitchesTheLanguage(): void
    {
        $this->open('admin/account/login');
        $this->postLanguage('de');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('de');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));

        // the account's language is only the fallback, so the pick outlives the login
        self::assertLanguageSame('de');
    }

    /**
     * The route is fixed and the language travels in the body, because the navbar holds whichever page it was last
     * rendered with — the current URL is only known to the browser.
     */
    public function testTheDropdownPostsTheLanguageToTheFixedRoute(): void
    {
        $this->login();
        $this->open('admin/user/index');

        self::assertSelectorExists('button.i18n-dropdown-option[hx-post="/admin/account/language"][hx-vals=\'{"language":"de"}\']');
        self::assertSelectorExists('button.i18n-dropdown-option[hx-post="/admin/account/language"][hx-vals=\'{"language":"en-US"}\']');

        // nothing outside `#wrap` inherits its CSRF header
        self::assertSelectorExists('.dropdown[hx-headers\\:inherited]');
    }

    public function testAnUnknownLanguageIsRefused(): void
    {
        $this->login();

        $this->postLanguage('fr');

        self::assertResponseStatusCodeSame(400);
        self::assertNull(self::getAdminModule()->getSessionLanguage());
    }

    public function testTheAccountLanguageReplacesTheSessionLanguage(): void
    {
        $user = $this->login();

        $this->postLanguage('de');
        self::assertSame('de', self::getAdminModule()->getSessionLanguage());

        $this->open('admin/account/update');
        $this->submit(values: $this->prefixFormValues($user, [
            'language' => 'en-US',
        ]));

        self::assertLanguageSame('en');
        self::assertNull(self::getAdminModule()->getSessionLanguage());
    }

    /**
     * @param array<string, mixed> $server
     */
    private function postLanguage(string $language, array $server = []): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/language', [
            'language' => $language,
            $request->csrfParam => $request->getCsrfToken(),
        ], [], $server);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('owner');

        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $user->email,
            'password' => 'password',
        ]));

        return $user;
    }

    private static function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }

    private static function assertLanguageSame(string $expected): void
    {
        self::assertSame($expected, self::$crawler->filter('html')->attr('lang'));
    }
}
