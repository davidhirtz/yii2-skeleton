<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\UrlManager;
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
     * The admin list is independent of the content languages.
     */
    public function testALanguageOutsideTheAdminListIsRefused(): void
    {
        self::getAdminModule()->languages = ['en-US'];
        $this->login();

        $this->postLanguage('de');

        self::assertResponseStatusCodeSame(400);
        self::assertNull(self::getAdminModule()->getSessionLanguage());
    }

    public function testAGuestOnlyGetsAnOfferedLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de', 'fr']);
        self::getAdminModule()->languages = ['en-US', 'de'];

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->defaultLanguage = 'fr';

        $this->open('admin/account/login');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('en');
    }

    public function testASingleLanguageIsPinnedAndHidesThePickers(): void
    {
        self::getAdminModule()->languages = ['de'];
        $this->login();

        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('de');
        self::assertSelectorNotExists('button[hx-post="/admin/account/language"]');
        self::assertSelectorNotExists('select[name$="[language]"]');
    }

    public function testAnAccountWithALanguageNoLongerOfferedStillSaves(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'en-US']);
        self::getAdminModule()->languages = ['de'];

        $this->login();
        $this->open('admin/account/update');
        self::assertSelectorNotExists('select[name$="[language]"]');

        $this->submit(values: $this->prefixFormValues($user, ['name' => 'Changed']));

        $user = User::findOne($user->id);
        self::assertSame('Changed', $user?->name);
        self::assertSame('de', $user->language);
    }

    public function testTheLoginFlashIsInTheAccountsLanguage(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'de']);

        $this->login();

        self::assertLanguageSame('de');
        self::assertStringNotContainsString('Login successful', self::$crawler->filter('#flashes')->text(''));
        self::assertStringNotContainsString('Welcome back', self::$crawler->filter('#flashes')->text(''));
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
     * The navbar renders outside `#wrap`, so the whole document is reloaded rather than swapped.
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
     * A login in another tab renews the token the navbar was rendered with.
     */
    public function testAStaleTokenReloadsThePageWithoutSwitching(): void
    {
        $this->login();

        $this->postLanguage('de', ['HTTP_HX_REQUEST' => 'true'], 'stale');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertNull(self::getAdminModule()->getSessionLanguage());
        self::assertSame([Yii::t('skeleton', 'ACCOUNT_PAGE_EXPIRED_ERROR')], $this->getWebSession()->getFlash('danger'));
    }

    public function testAStalePlainRequestRedirectsToTheReferrerWithoutSwitching(): void
    {
        $this->login();

        self::$client->followRedirects(false);
        $this->postLanguage('de', ['HTTP_REFERER' => 'https://www.test.localhost/admin/user/index'], 'stale');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', 'https://www.test.localhost/admin/user/index');
        self::assertNull(self::getAdminModule()->getSessionLanguage());
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
     * The navbar holds whichever page it was last rendered with, so the language travels in the body.
     */
    public function testTheDropdownPostsTheLanguageToTheFixedRoute(): void
    {
        $this->login();
        $this->open('admin/user/index');

        self::assertSelectorExists('button.dropdown-option[hx-post="/admin/account/language"][hx-vals=\'{"language":"de"}\']');
        self::assertSelectorExists('button.dropdown-option[hx-post="/admin/account/language"][hx-vals=\'{"language":"en-US"}\']');

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
    private function postLanguage(string $language, array $server = [], ?string $token = null): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/language', [
            'language' => $language,
            $request->csrfParam => $token ?? $request->getCsrfToken(),
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
