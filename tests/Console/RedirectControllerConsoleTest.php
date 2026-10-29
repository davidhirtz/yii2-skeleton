<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\RedirectController;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;

/**
 * Separate from {@see RedirectControllerTest}, since the host list is read off a console application.
 */
class RedirectControllerConsoleTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testTheCommandIsRegistered(): void
    {
        self::assertSame(RedirectController::class, Application::current()->coreCommands()['redirect'] ?? null);
    }

    /**
     * `UrlManager::getHostInfo()` throws under a console application rather than answering `null`.
     */
    public function testAnUnconfiguredHostInfoIsReportedRatherThanThrown(): void
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'old';
        $redirect->url = 'new';
        $redirect->insert();

        $controller = new ConsoleRedirectControllerMock('redirect', Application::current());
        $controller->interactive = false;
        $controller->actionClean();

        $output = $controller->flushStdOutBuffer();

        self::assertStringContainsString('No hosts known', $output);
        self::assertStringContainsString('Nothing to clean up', $output);
        self::assertSame(1, (int)Redirect::find()->count());
    }
}

class ConsoleRedirectControllerMock extends RedirectController
{
    use StdOutBufferControllerTrait;
}
