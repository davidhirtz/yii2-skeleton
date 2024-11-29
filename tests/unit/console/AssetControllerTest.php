<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\assets\AdminAsset;
use davidhirtz\yii2\skeleton\codeception\traits\ConsoleApplicationTrait;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\AssetController;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

class AssetControllerTest extends Unit
{
    use ConsoleApplicationTrait;

    protected string $assetBasePath = '@runtime/assets';

    protected function _before(): void
    {
        FileHelper::createDirectory($this->assetBasePath);

        $this->createConsoleApplicationMock();
        Yii::$app->getAssetManager()->basePath = Yii::getAlias($this->assetBasePath);
        AdminAsset::register(Yii::$app->getView());

        parent::_before();
    }

    protected function _after(): void
    {
        FileHelper::removeDirectory($this->assetBasePath);
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
