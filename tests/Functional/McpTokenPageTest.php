<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class McpTokenPageTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config['components']['mcp'] = ['enableHttp' => true];
        $this->reloadApplication();
    }

    public function testATokenIsCreatedOnItsOwnPageAndShownOnce(): void
    {
        $this->login();
        $this->open('admin/mcp-token/index');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.tabs a[href="/admin/mcp-token/index"]');
        self::assertSelectorNotExists('.tabs a[href="/admin/grant/index"]');
        self::assertSelectorNotExists('#mcp-token');
        self::assertSelectorNotExists('form select');
        self::assertSelectorExists('a[href="/admin/mcp-token/create"]');

        $this->open('admin/mcp-token/create');

        self::assertSelectorExists('label[for="mcptokenform-expires"]');
        self::assertSelectorExists('select[name="McpTokenForm[expires]"] option[value="90"][selected]');
        self::assertSelectorExists('input[type="checkbox"][name="McpTokenForm[readOnly]"]');

        $this->submit(values: $this->prefixFormValues('McpTokenForm', ['readOnly' => '1', 'expires' => '30']));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#mcp-token');

        $token = trim(self::$crawler->filter('#mcp-token')->text());
        $record = Mcp::getComponent()->findToken($token);

        self::assertNotNull($record);
        self::assertSame(UserToken::TYPE_MCP_READ, $record->type);
        self::assertSame($this->getUserFromFixture('owner')->id, $record->user_id);
        self::assertNotNull($record->expires_at);
        self::assertEqualsWithDelta(time() + 30 * 86400, $record->expires_at->getTimestamp(), 60);

        $this->open('admin/mcp-token/index');
        self::assertSelectorNotExists('#mcp-token');
        self::assertSelectorExists("#user-token-$record->id");
    }

    public function testNeverIsAChoiceAndAnUnlistedLifetimeIsRefused(): void
    {
        $this->login();
        $this->open('admin/mcp-token/create');
        $this->submit(values: $this->prefixFormValues('McpTokenForm', ['expires' => '']));

        $record = Mcp::getComponent()->findToken(trim(self::$crawler->filter('#mcp-token')->text()));

        self::assertSame(UserToken::TYPE_MCP_WRITE, $record?->type);
        self::assertNull($record->expires_at);

        $count = (int)UserToken::find()->whereUser($this->getUserFromFixture('owner')->id)->count();

        $this->open('admin/mcp-token/create');
        $request = $this->getWebRequest();
        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/mcp-token/create', [
            'McpTokenForm' => ['expires' => '1000'],
            $request->csrfParam => $request->getCsrfToken(),
        ]);

        self::assertSelectorExists('.has-error, .is-invalid, [aria-invalid="true"]');
        self::assertSame($count, (int)UserToken::find()->whereUser($this->getUserFromFixture('owner')->id)->count());
    }

    public function testATokenIsRevoked(): void
    {
        $owner = $this->getUserFromFixture('owner');
        Mcp::getComponent()->issueToken($owner, false);
        $record = UserToken::find()->whereUser($owner->id)->whereType(UserToken::TYPE_MCP_WRITE)->orderBy(['id' => SORT_DESC])->one();
        self::assertNotNull($record);

        $this->login();
        $this->open('admin/mcp-token/index');
        $this->postRevoke($record->id);

        self::assertAlertSame('The token was revoked.', 'success');
        self::assertNull(UserToken::findOne($record->id));
    }

    public function testAManagerSeesAndRevokesAnotherUsersToken(): void
    {
        $admin = $this->getUserFromFixture('admin');
        Mcp::getComponent()->issueToken($admin, true);
        $record = UserToken::find()->whereUser($admin->id)->whereType(UserToken::TYPE_MCP_READ)->one();
        self::assertNotNull($record);

        $this->login();
        $this->open("admin/grant/view?user=$admin->id");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("#user-token-$record->id");
        self::assertSelectorNotExists('a[href="/admin/mcp-token/create"]');

        self::$client->followRedirects(false);
        $this->postRevoke($record->id);

        self::assertResponseHeaderSame('location', "https://www.test.localhost/admin/grant/view?user=$admin->id");
        self::assertNull(UserToken::findOne($record->id));
    }

    public function testWithoutOAuthTheConnectedAppsAreAbsent(): void
    {
        $this->login();

        $this->open('admin/grant/authorize');
        self::assertResponseStatusCodeSame(404);

        $this->open('admin/grant/index');
        self::assertResponseStatusCodeSame(404);
    }

    public function testWithoutHttpThePagesAreAbsent(): void
    {
        $this->config['components']['mcp']['enableHttp'] = false;
        $this->reloadApplication();

        $this->login();
        $this->open('admin/mcp-token/index');

        self::assertResponseStatusCodeSame(404);

        $this->open('admin/account/update');
        self::assertSelectorNotExists('.tabs a[href="/admin/mcp-token/index"]');
    }

    private function postRevoke(int|string $id): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', "https://www.test.localhost/admin/mcp-token/revoke?id=$id", [
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    private function login(): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }
}
