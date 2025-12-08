<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\console;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Codeception\traits\AssetDirectoryTrait;
use Hirtz\Skeleton\Codeception\traits\ConsoleApplicationTrait;
use Hirtz\Skeleton\Codeception\traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Console\Controllers\AssetController;
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
