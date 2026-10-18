<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Credentials\CredentialProvider;
use Hirtz\Skeleton\Credentials\Credentials;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\UserCredential;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class CredentialPageTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Assigned, not configured: the config would append to a sibling bundle's providers
        Credentials::getComponent()->providers = [
            fn (): CredentialProvider => CredentialProvider::make('acme')
                ->label('Äcme Bilder 🎨')
                ->url('https://acme.example/keys')
                ->verify(fn (string $secret): bool => $secret !== 'wrong'),
        ];
    }

    public function testAKeyIsSavedShownByItsHintAndRemoved(): void
    {
        $this->login();
        $this->open('admin/credential/index');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.tabs a[href="/admin/credential/index"]');
        self::assertSelectorNotExists('form input[type="password"]');
        self::assertSelectorExists('.header a[href="/admin/credential/create"]');
        self::assertSelectorExists('a.nav-link.active[href="/admin/account/update"]');
        self::assertSelectorNotExists('a.nav-link.active[href="/admin/user/index"]');

        $this->open('admin/credential/create');
        self::assertSelectorExists('a.nav-link.active[href="/admin/account/update"]');
        self::assertSelectorNotExists('a.nav-link.active[href="/admin/user/index"]');
        // a single service is no choice
        self::assertSelectorExists('input[type="hidden"][name="UserCredentialForm[provider]"][value="acme"]');
        self::assertSelectorExists('label[for="usercredentialform-secret"]');
        self::assertAnySelectorTextContains('.form-hint', 'https://acme.example/keys');

        $this->submit(values: $this->prefixFormValues('UserCredentialForm', ['provider' => 'acme', 'secret' => 'sk-test-wxyz']));

        self::assertAlertSame('The key was saved.', 'success');
        self::assertAnySelectorTextContains('td', '…wxyz');
        self::assertAnySelectorTextContains('td', 'Äcme Bilder 🎨');
        self::assertSelectorTextNotContains('body', 'sk-test-wxyz');

        $record = UserCredential::findOne(['user_id' => $this->getUserFromFixture('owner')->id]) ?? self::fail('No key');

        $this->post("admin/credential/delete?id=$record->id");
        self::assertAlertSame('The key was removed.', 'success');
        self::assertNull(UserCredential::findOne($record->id));
    }

    public function testAKeyTheServiceRefusesIsNotSaved(): void
    {
        $this->login();
        $this->open('admin/credential/create');
        $this->submit(values: $this->prefixFormValues('UserCredentialForm', ['provider' => 'acme', 'secret' => 'wrong']));

        self::assertAnySelectorTextContains('body', 'The service did not accept this key.');
        self::assertNull(UserCredential::findOne(['user_id' => $this->getUserFromFixture('owner')->id]));
    }

    public function testAManagerSeesAndRemovesAUsersKey(): void
    {
        $admin = $this->getUserFromFixture('admin');
        $record = UserCredential::store($admin->id, 'acme', 'sk-users-key-wxyz');

        $this->login();
        $this->open("admin/credential/view?user=$admin->id");

        self::assertResponseIsSuccessful();
        self::assertAnySelectorTextContains('td', '…wxyz');
        self::assertSelectorTextNotContains('body', 'sk-users-key-wxyz');
        self::assertSelectorNotExists('a[href="/admin/credential/create"]');
        self::assertSelectorExists(".tabs a.active[href=\"/admin/credential/view?user=$admin->id\"]");
        self::assertSelectorExists('a.nav-link.active[href="/admin/user/index"]');
        self::assertSelectorNotExists('a.nav-link.active[href="/admin/account/update"]');

        self::$client->followRedirects(false);
        $this->post("admin/credential/delete?id=$record->id");

        self::assertResponseHeaderSame('location', "https://www.test.localhost/admin/credential/view?user=$admin->id");
        self::assertNull(UserCredential::findOne($record->id));
    }

    public function testAKeyOfAUserOneCannotManageIsNotFound(): void
    {
        $record = UserCredential::store($this->getUserFromFixture('owner')->id, 'acme', 'not-yours');

        // the fixture's admin has two-factor authentication, which a login form test does not go through
        $this->getWebUser()->enableTwoFactorAuthentication = false;
        $this->login('admin');
        $this->open('admin/credential/index');
        self::assertResponseIsSuccessful();

        self::$client->followRedirects(false);
        $this->post("admin/credential/delete?id=$record->id");

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull(UserCredential::findOne($record->id));
    }

    public function testWithoutProvidersThePageIsAbsent(): void
    {
        Credentials::getComponent()->providers = [];

        $this->login();
        $this->open('admin/credential/index');

        self::assertResponseStatusCodeSame(404);
    }

    private function post(string $url): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', "https://www.test.localhost/$url", [
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    private function login(string $fixtureKey = 'owner'): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture($fixtureKey)->email,
            'password' => 'password',
        ]));
    }
}
