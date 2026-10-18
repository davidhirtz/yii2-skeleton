<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\McpController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use yii\base\InlineAction;

class McpControllerTest extends TestCase
{
    use UserFixtureTrait;

    protected string $applicationClass = Application::class;

    public function testTheServeOptionsAreDeclared(): void
    {
        $controller = new McpController('mcp', Application::current());

        self::assertContains('user', $controller->options('serve'));
        self::assertContains('readOnly', $controller->options('serve'));
    }

    public function testTheServerRunsAsTheOwner(): void
    {
        $controller = new McpController('mcp', Application::current());
        $action = new InlineAction('serve', $controller, 'actionServe');

        self::assertTrue($controller->beforeAction($action));
        self::assertSame($this->getUserFromFixture('owner')->id, WebUser::current()?->getId());
    }
}
