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

    /**
     * The language travels in the body rather than as `Request::$languageParam`, which the URL manager would turn
     * into the path prefix of the frontend language.
     */
    public function testTheDropdownPostsToTheAdminsOwnRoute(): void
    {
        $this->login();
        $this->open('admin/user/index');

        self::assertSelectorExists('button.i18n-dropdown-option[hx-post="/en/admin/account/language"][hx-vals=\'{"language":"de"}\']');

        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/en/admin/account/language', [
            'language' => 'de',
            $request->csrfParam => $request->getCsrfToken(),
        ]);

        $this->open('admin/user/index');
        self::assertLanguageSame('de');
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

    private static function assertLanguageSame(string $expected): void
    {
        self::assertSame($expected, self::$crawler->filter('html')->attr('lang'));
    }
}
