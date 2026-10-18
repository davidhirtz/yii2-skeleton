<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\McpController;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use yii\base\InlineAction;
use yii\console\ExitCode;

class McpControllerTest extends TestCase
{
    use UserFixtureTrait;

    protected string $applicationClass = Application::class;

    public function testTheOptionsAreDeclaredPerAction(): void
    {
        $controller = new McpController('mcp', Application::current());

        self::assertContains('readOnly', $controller->options('serve'));
        self::assertContains('expires', $controller->options('token'));
        self::assertContains('user', $controller->options('revoke'));
        self::assertNotContains('readOnly', $controller->options('tokens'));
    }

    public function testTheServerRunsAsTheOwner(): void
    {
        $this->createController();
        self::assertSame($this->getUserFromFixture('owner')->id, WebUser::current()?->getId());
    }

    public function testATokenIsShownOnceAndAuthenticates(): void
    {
        $controller = $this->createController('token');
        $controller->readOnly = true;
        $controller->expires = 30;

        self::assertSame(ExitCode::OK, $controller->actionToken());

        $output = $controller->flushStdOutBuffer();
        preg_match('/^([\w-]{48})$/m', $output, $matches);
        $issued = $matches[1] ?? self::fail("No token in: $output");

        $token = Mcp::getComponent()->findToken($issued);

        self::assertSame(UserToken::TYPE_MCP_READ, $token?->type);
        self::assertSame($this->getUserFromFixture('owner')->id, $token->user_id);
        self::assertNotNull($token->expires_at);
        self::assertStringNotContainsString($issued, (string)UserToken::find()->select('token')->where(['id' => $token->id])->scalar());
    }

    public function testTokensAreListedWithoutTheToken(): void
    {
        $token = Mcp::getComponent()->issueToken($this->getUserFromFixture('owner'), false);
        $controller = $this->createController('tokens');

        self::assertSame(ExitCode::OK, $controller->actionTokens());

        $output = $controller->flushStdOutBuffer();

        self::assertStringContainsString('mcp-write', $output);
        self::assertStringContainsString('never expires', $output);
        self::assertStringNotContainsString($token, $output);
    }

    public function testTheTokensOfAnOAuthGrantAreNotListed(): void
    {
        $grant = OAuthGrant::create();
        $grant->user_id = $this->getUserFromFixture('owner')->id;
        $grant->client_id = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
        $grant->resource = 'https://www.example.com/mcp';
        $grant->scope = OAuthGrant::SCOPE_WRITE;
        $grant->insert();

        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_WRITE, 'access-token', 60, $grant->id);
        $controller = $this->createController('tokens');

        self::assertSame(ExitCode::OK, $controller->actionTokens());
        self::assertStringContainsString('No MCP tokens.', $controller->flushStdOutBuffer());
    }

    public function testRevokeRemovesTheToken(): void
    {
        $mcp = Mcp::getComponent();
        $owner = $this->getUserFromFixture('owner');

        $first = $mcp->issueToken($owner, false);
        $second = $mcp->issueToken($owner, true);
        $id = (int)$mcp->findToken($first)?->id;

        $controller = $this->createController('revoke');

        self::assertSame(ExitCode::OK, $controller->actionRevoke($id));
        self::assertNull($mcp->findToken($first));
        self::assertNotNull($mcp->findToken($second));

        self::assertSame(ExitCode::DATAERR, $controller->actionRevoke($id));

        self::assertSame(ExitCode::OK, $controller->actionRevoke());
        self::assertNull($mcp->findToken($second));
    }

    public function testAnotherUsersTokenIsNotRevoked(): void
    {
        $mcp = Mcp::getComponent();
        $token = $mcp->issueToken($this->getUserFromFixture('admin'), false);

        $controller = $this->createController('revoke');

        self::assertSame(ExitCode::DATAERR, $controller->actionRevoke((int)$mcp->findToken($token)?->id));
        self::assertNotNull($mcp->findToken($token));
    }

    private function createController(string $action = 'serve'): McpControllerMock
    {
        $controller = new McpControllerMock('mcp', Application::current());
        self::assertTrue($controller->beforeAction(new InlineAction($action, $controller, 'action' . ucfirst($action))));

        return $controller;
    }
}

class McpControllerMock extends McpController
{
    use StdOutBufferControllerTrait;
}
