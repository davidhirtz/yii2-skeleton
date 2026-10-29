<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\HelpController;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Test\TestCase;
use ReflectionMethod;
use yii\base\InlineAction;
use yii\console\Controller;
use yii\console\UnknownCommandException;

class HelpControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    /**
     * Building a web controller throws under the console, which has no `user` component.
     */
    public function testGetCommandsSkipsWebControllersInControllerMap(): void
    {
        Application::current()->getModule('admin')->controllerMap['dashboard'] = DashboardController::class;

        $commands = (new HelpController('help', Application::current()))->getCommands();

        self::assertNotContains('admin/dashboard', $commands);
        self::assertContains('migrate', $commands);
    }

    public function testUnknownCommandSuggestsAlternatives(): void
    {
        Application::current()->getModule('admin')->controllerMap['dashboard'] = DashboardController::class;

        $exception = new UnknownCommandException('migrat', Application::current());

        self::assertContains('migrate', $exception->getSuggestedAlternatives());
    }

    public function testHelpCommandResolvesToOverriddenController(): void
    {
        $route = Application::current()->createController('help');
        self::assertNotFalse($route);

        [$controller] = $route;

        self::assertInstanceOf(HelpController::class, $controller);
    }

    /**
     * `./yii help` lists only the first docblock line, so a wrapped sentence is shown cut off.
     */
    public function testEverySummaryIsOneCompleteLine(): void
    {
        $app = Application::current();
        $help = new HelpController('help', $app);
        $missing = [];

        foreach ($help->getCommands() as $command) {
            $controller = $app->createController($command)[0] ?? null;

            if (!$controller instanceof Controller || !str_starts_with($controller::class, 'Hirtz\\')) {
                continue;
            }

            if (!str_ends_with($controller->getHelpSummary(), '.')) {
                $missing[] = $command;
            }

            foreach ($help->getActions($controller) as $id) {
                $action = $controller->createAction($id);

                if (!$action instanceof InlineAction) {
                    continue;
                }

                $method = new ReflectionMethod($controller, $action->actionMethod);

                // What `Controller::getActionHelpSummary()` reads, which Yii types too narrowly to be handed the action.
                $summary = trim(preg_split('/\R/', (string)$method->getDocComment())[1] ?? '', "\t *");

                if (str_starts_with($method->class, 'Hirtz\\') && !str_ends_with($summary, '.')) {
                    $missing[] = "$command/$id";
                }
            }
        }

        self::assertSame([], $missing);
    }
}
