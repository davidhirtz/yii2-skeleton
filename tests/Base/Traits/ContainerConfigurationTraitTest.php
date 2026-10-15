<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Base\Traits;

use Hirtz\Skeleton\Modules\Admin\Widgets\DirectoryAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ExtensionVersions;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\base\InvalidConfigException;

class ContainerConfigurationTraitTest extends TestCase
{
    public function testAContainerDefinitionCallsTheSetterOfAProtectedOption(): void
    {
        Yii::$container->set(ExtensionVersions::class, ['excluded' => ['*/yii2-skeleton']]);

        $widget = ExtensionVersions::make();
        $widget->render();

        self::assertArrayNotHasKey('davidhirtz/yii2-skeleton', $widget->getExtensions());
    }

    public function testAConfigArraySpreadsIntoAVariadicSetter(): void
    {
        $alert = DirectoryAlert::make([
            'directories' => ['@runtime'],
            'addDirectories' => ['@nothing-defines-this'],
        ]);

        $alert->render();

        self::assertSame(['@nothing-defines-this'], $alert->getUnwritable());
    }

    public function testAKeyNamingNeitherPropertyNorMethodThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        DirectoryAlert::make(['unknownOption' => true]);
    }
}
