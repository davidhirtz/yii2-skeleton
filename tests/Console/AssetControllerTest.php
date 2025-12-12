<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\AssetController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class AssetControllerTest extends TestCase
{
    public function testActionClear(): void
    {
        AdminAssetBundle::register(Yii::$app->getView());

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
