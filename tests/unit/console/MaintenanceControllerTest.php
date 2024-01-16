<?php

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
        $this->assertMaintenanceModeEnabled($controller);

        $controller->actionIndex();
        $this->assertMaintenanceModeDisabled($controller);
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
        $this->assertMaintenanceModeEnabled($controller);

        $config = Yii::getAlias(MaintenanceConfigForm::MAINTENANCE_CONFIG);
        $this->assertFileExists($config);

        $config = json_decode(file_get_contents($config), true);

        $this->assertEquals('https://example.com', $config['redirect']);
        $this->assertEquals(1, $config['retry']);
        $this->assertEquals(2, $config['refresh']);
        $this->assertEquals(500, $config['status']);

        $template = file_get_contents(Yii::getAlias($controller->viewFile));

        $this->assertEquals($template, $config['template']);

        $controller->actionDisable();
        $this->assertMaintenanceModeDisabled($controller);
    }

    protected function assertMaintenanceModeEnabled(MaintenanceControllerMock $controller): void
    {
        $this->assertEquals('Maintenance mode enabled.' . PHP_EOL, $controller->flushStdOutBuffer());
        $this->assertFileExists(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
    }

    protected function assertMaintenanceModeDisabled(MaintenanceControllerMock $controller): void
    {
        $this->assertEquals('Maintenance mode disabled.' . PHP_EOL, $controller->flushStdOutBuffer());
        $this->assertFileNotExists(Yii::getAlias(MaintenanceController::MAINTENANCE_FILE));
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
