<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\ConsoleApplicationTrait;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\MaintenanceController;
use davidhirtz\yii2\skeleton\models\forms\MaintenanceConfigForm;
use Yii;

class MaintenanceControllerTest extends Unit
{
    use ConsoleApplicationTrait;

    protected function _after(): void
    {
        @unlink(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
        @unlink(Yii::getAlias(MaintenanceConfigForm::MAINTENANCE_CONFIG));

        parent::_after();
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

        $controller->redirect = 'https://example.com';
        $controller->retry = 1;
        $controller->refresh = 2;
        $controller->statusCode = 500;
        $controller->viewFile = '@tests/data/views/maintenance.php';

        $controller->actionEnable();
        self::assertMaintenanceModeEnabled($controller);

        $config = Yii::getAlias(MaintenanceConfigForm::MAINTENANCE_CONFIG);
        self::assertFileExists($config);

        $config = json_decode(file_get_contents($config), true);

        self::assertEquals('https://example.com', $config['redirect']);
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
        self::assertFileNotExists(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
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
