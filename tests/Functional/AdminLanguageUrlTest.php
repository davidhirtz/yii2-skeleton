<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

/**
 * `UrlManager::$i18nUrl` is the language of the frontend URL, the admin keeps its own.
 */
class AdminLanguageUrlTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        $config = require(__DIR__ . '/../../config/test.php');
        $config['components']['i18n']['languages'] = ['en-US', 'de'];
        $config['components']['urlManager']['i18nUrl'] = true;
        $config['components']['urlManager']['defaultLanguage'] = 'de';

        $this->config = $config;

        parent::setUp();
    }

    public function testThePathDoesNotSetTheAdminLanguage(): void
    {
        $this->login();

        $this->open('de/admin/user/index');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('en');
    }

    public function testAnAdminUrlHasNoLanguagePrefix(): void
    {
        $this->open('admin/account/login');

        self::assertSelectorExists('button.dropdown-option[hx-post="/admin/account/language"][hx-vals=\'{"language":"de"}\']');

        $this->login(false);
        $this->open('admin/user/index');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('button.dropdown-option[hx-post="/admin/account/content-language"][hx-vals=\'{"language":"en-US"}\']');
        self::assertSelectorExists('a[href="/admin/user/create"]');
    }

    public function testAPrefixedAdminPathIsRedirected(): void
    {
        $this->login();

        self::$client->followRedirects(false);
        self::$crawler = self::$client->request('GET', 'https://www.test.localhost/en/admin/user/index?page=1');

        self::assertResponseStatusCodeSame(301);
        self::assertResponseHeaderSame('location', 'https://www.test.localhost/admin/user/index?page=1');
    }

    /**
     * Links to the site follow the content language, not the admin's.
     */
    public function testALinkToTheSiteFollowsTheContentLanguage(): void
    {
        $this->getUserFromFixture('owner')->updateAttributes(['language' => 'de']);
        $this->login();

        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/content-language', [
            'language' => 'en-US',
            $request->csrfParam => $request->getCsrfToken(),
        ]);

        $this->open('admin/dashboard/index');

        self::assertLanguageSame('de');
        self::assertSame('/en/test', Yii::$app->getUrlManager()->createUrl(['test']));
    }

    private function login(bool $open = true): User
    {
        $user = $this->getUserFromFixture('owner');

        if ($open) {
            $this->open('admin/account/login');
        }

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $user->email,
            'password' => 'password',
        ]));

        return $user;
    }

    private static function assertLanguageSame(string $expected): void
    {
        self::assertSame($expected, self::$crawler->filter('html')->attr('lang'));
    }
}
