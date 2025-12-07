<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\assets\AdminAssetBundle;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;
use davidhirtz\yii2\skeleton\codeception\traits\ConsoleApplicationTrait;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\AssetController;
use Yii;

class AssetControllerTest extends Unit
{
    use AssetDirectoryTrait;
    use ConsoleApplicationTrait;

    protected function _before(): void
    {
        $this->createConsoleApplicationMock();
        $this->createAssetDirectory();
        AdminAssetBundle::register(Yii::$app->getView());

        parent::_before();
    }

    protected function _after(): void
    {
        $this->removeAssetDirectory();
        parent::_after();
    }

    public function testActionClear(): void
    {
        $controller = new AssetControllerMock('asset', Yii::$app);

        $controller->actionClear();
        self::assertStringStartsWith('Removing ', $controller->flushStdOutBuffer());

        $controller->actionClear();
        self::assertStringStartsWith('All assets are already cleared', $controller->flushStdOutBuffer());
    }
}


class AssetControllerMock extends AssetController
{
    use StdOutBufferControllerTrait;
}
