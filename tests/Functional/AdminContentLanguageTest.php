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

class AdminContentLanguageTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testTheContentLanguageDefaultsToTheAdminLanguage(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'de']);

        $this->login($user);
        $this->open('admin/user/index');

        self::assertResponseIsSuccessful();
        self::assertSame('de', self::getUrlManager()->getContentLanguage());
        self::assertSame('de', Yii::$app->getI18n()->getContentLanguage());
    }

    public function testAnAdminLanguageTheSiteDoesNotOfferFallsBackToTheSitesDefault(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'de']);

        $manager = self::getUrlManager();
        $manager->languages = ['en-US' => 'en', 'fr' => 'fr'];
        $manager->defaultLanguage = 'fr';

        $this->login($user);
        $this->open('admin/user/index');

        self::assertSame('fr', self::getUrlManager()->getContentLanguage());
        self::assertSame('de', Yii::$app->language);
    }

    public function testThePickedContentLanguageLeavesTheAdminLanguageAlone(): void
    {
        $this->login();
        $this->open('admin/user/index');

        self::assertSelectorExists('button.dropdown-option[hx-post="/admin/account/content-language"][hx-vals=\'{"language":"de"}\']');
        self::assertSelectorNotExists('button[hx-post="/admin/account/language"]');

        $this->postContentLanguage('de', ['HTTP_HX_REQUEST' => 'true']);

        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertSame('de', self::getAdminModule()->getSessionContentLanguage());

        $this->open('admin/user/index');

        self::assertSame('en', self::$crawler->filter('html')->attr('lang'));
        self::assertSame('de', self::getUrlManager()->getContentLanguage());
        self::assertSelectorExists('button.dropdown-option.selected[hx-vals=\'{"language":"de"}\']');
    }

    public function testAContentLanguageTheSiteDoesNotOfferIsRefused(): void
    {
        $this->login();

        $this->postContentLanguage('fr');

        self::assertResponseStatusCodeSame(400);
        self::assertNull(self::getAdminModule()->getSessionContentLanguage());
    }

    public function testAGuestCannotPickAContentLanguage(): void
    {
        $this->open('admin/account/login');

        self::assertSelectorExists('button[hx-post="/admin/account/language"]');
        self::assertSelectorNotExists('button[hx-post="/admin/account/content-language"]');

        self::$client->followRedirects(false);
        $this->postContentLanguage('de');

        self::assertNull(self::getAdminModule()->getSessionContentLanguage());
    }

    public function testASingleContentLanguageHidesTheDropdown(): void
    {
        self::getUrlManager()->languages = ['de' => 'de'];

        $this->login();
        $this->open('admin/user/index');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('button[hx-post="/admin/account/content-language"]');
        self::assertSame('de', self::getUrlManager()->getContentLanguage());
    }

    public function testAPickTheSiteNoLongerOffersIsDropped(): void
    {
        $module = self::getAdminModule();
        $module->setSessionContentLanguage('de');

        self::assertSame('de', $module->getSessionContentLanguage());

        self::getUrlManager()->languages = ['en-US' => 'en', 'fr' => 'fr'];

        self::assertNull($module->getSessionContentLanguage());
        self::assertSame('en-US', $module->getContentLanguage());
    }

    public function testTheAccountLanguageWinsAtLogin(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'en-US']);

        $this->open('admin/account/login');
        $this->postLanguage('de');
        $this->login($user, false);

        self::assertSame('en', self::$crawler->filter('html')->attr('lang'));
        self::assertNull(self::getAdminModule()->getSessionLanguage());
        self::assertSame('en-US', User::findOne($user->id)?->language);
    }

    public function testTheLoginPagesLanguageGoesToAnAccountWithoutOne(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->updateAttributes(['language' => 'fr']);

        $this->open('admin/account/login');
        $this->postLanguage('de');
        $this->login($user, false);

        self::assertSame('de', self::$crawler->filter('html')->attr('lang'));
        self::assertNull(self::getAdminModule()->getSessionLanguage());
        self::assertSame('de', User::findOne($user->id)?->language);
    }

    /**
     * @param array<string, mixed> $server
     */
    private function postContentLanguage(string $language, array $server = []): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/content-language', [
            'language' => $language,
            $request->csrfParam => $request->getCsrfToken(),
        ], [], $server);
    }

    private function postLanguage(string $language): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/language', [
            'language' => $language,
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    private function login(?User $user = null, bool $open = true): User
    {
        $user ??= $this->getUserFromFixture('owner');

        if ($open) {
            $this->open('admin/account/login');
        }

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

    private static function getUrlManager(): UrlManager
    {
        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);

        return $manager;
    }
}
