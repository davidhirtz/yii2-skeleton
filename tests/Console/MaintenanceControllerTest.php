<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Console\Controllers\MaintenanceController;
use Hirtz\Skeleton\Models\Forms\MaintenanceConfigForm;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class MaintenanceControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
        @unlink(Yii::getAlias(MaintenanceConfigForm::MAINTENANCE_CONFIG));

        parent::tearDown();
    }

    public function testActionIndex(): void
    {
        $controller = $this->createMaintenanceController();

        $controller->actionIndex();
        self::assertMaintenanceModeEnabled($controller);

        $controller->actionIndex();
        self::assertMaintenanceModeDisabled($controller);
    }

    public function testActionEnableAndDisable(): void
    {
        $controller = $this->createMaintenanceController();

        $controller->redirect = 'https://test.localhost';
        $controller->retry = 1;
        $controller->refresh = 2;
        $controller->statusCode = 500;
        $controller->viewFile = '@skeleton/../resources/tests/views/maintenance.html';

        $controller->actionEnable();
        self::assertMaintenanceModeEnabled($controller);

        $config = Yii::getAlias(MaintenanceConfigForm::MAINTENANCE_CONFIG);
        self::assertFileExists($config);

        $config = json_decode(file_get_contents($config), true);

        self::assertEquals('https://test.localhost', $config['redirect']);
        self::assertEquals(1, $config['retry']);
        self::assertEquals(2, $config['refresh']);
        self::assertEquals(500, $config['status']);

        $template = file_get_contents(Yii::getAlias($controller->viewFile));

        self::assertEquals($template, $config['template']);

        $controller->actionDisable();
        self::assertMaintenanceModeDisabled($controller);
    }

    protected function assertMaintenanceModeEnabled(MaintenanceControllerMock $controller): void
    {
        self::assertEquals('Maintenance mode enabled.' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertFileExists(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
    }

    protected function assertMaintenanceModeDisabled(MaintenanceControllerMock $controller): void
    {
        self::assertEquals('Maintenance mode disabled.' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertFileDoesNotExist(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
    }

    protected function createMaintenanceController(): MaintenanceControllerMock
    {
        return new MaintenanceControllerMock('maintenance', Yii::$app);
    }
}

class MaintenanceControllerMock extends MaintenanceController
{
    use StdOutBufferControllerTrait;
}
