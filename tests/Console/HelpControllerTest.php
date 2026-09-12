<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\HelpController;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\console\UnknownCommandException;

class HelpControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    /**
     * A web controller in a module's `controllerMap` must be skipped by reflection: the console application has no
     * `user` component, so building one to find out what it is throws while an unknown command is being reported.
     */
    public function testGetCommandsSkipsWebControllersInControllerMap(): void
    {
        Yii::$app->getModule('admin')->controllerMap['dashboard'] = DashboardController::class;

        $commands = (new HelpController('help', Yii::$app))->getCommands();

        self::assertNotContains('admin/dashboard', $commands);
        self::assertContains('migrate', $commands);
    }

    public function testUnknownCommandSuggestsAlternatives(): void
    {
        Yii::$app->getModule('admin')->controllerMap['dashboard'] = DashboardController::class;

        $exception = new UnknownCommandException('migrat', Yii::$app);

        self::assertContains('migrate', $exception->getSuggestedAlternatives());
    }

    public function testHelpCommandResolvesToOverriddenController(): void
    {
        [$controller] = Yii::$app->createController('help');

        self::assertInstanceOf(HelpController::class, $controller);
    }
}
