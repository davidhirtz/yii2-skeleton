<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Assets\AdminAssetBundle;
use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\AssetController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;

class AssetControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    public function testActionClear(): void
    {
        AdminAssetBundle::register(Application::current()->getView());

        $controller = new AssetControllerMock('asset', Application::current());

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
