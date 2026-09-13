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

    public function testTheLanguageParamOutlivesTheRequestThatSetIt(): void
    {
        $this->login();

        $this->open('admin?language=de');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('de');

        $this->open('admin');
        self::assertLanguageSame('de');

        $this->open('admin?language=en-US');
        self::assertLanguageSame('en');
    }

    /**
     * A guest has no account to fall back to, and the login page is where the language is picked first.
     */
    public function testTheLoginPageSwitchesTheLanguage(): void
    {
        $this->open('admin/account/login?language=de');

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
     * The navbar is outside `#wrap` and would keep its previous language after a boosted swap.
     */
    public function testTheDropdownLinksToTheCurrentUrlWithoutBoost(): void
    {
        $this->login();
        $this->open('admin/user/index');

        self::assertSelectorExists('a.i18n-dropdown-option[href="/admin/user/index?language=de"][hx-boost="false"]');
        self::assertSelectorExists('a.i18n-dropdown-option[href="/admin/user/index?language=en-US"][hx-boost="false"]');
    }

    public function testAnUnknownLanguageIsIgnored(): void
    {
        $this->login();

        $this->open('admin?language=fr');

        self::assertResponseIsSuccessful();
        self::assertLanguageSame('en');
        self::assertNull(Yii::$app->getI18n()->getSessionLanguage());
    }

    public function testTheAccountLanguageReplacesTheSessionLanguage(): void
    {
        $user = $this->login();

        $this->open('admin?language=de');
        self::assertLanguageSame('de');

        $this->open('admin/account/update');
        $this->submit(values: $this->prefixFormValues($user, [
            'language' => 'en-US',
        ]));

        self::assertLanguageSame('en');
        self::assertNull(Yii::$app->getI18n()->getSessionLanguage());
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
