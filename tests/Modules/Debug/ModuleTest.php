<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Debug;

use Hirtz\Skeleton\Assets\EmptyAssetBundle;
use Hirtz\Skeleton\Modules\Debug\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\View;
use ReflectionMethod;
use Yii;
use yii\grid\GridViewAsset;
use yii\web\JqueryAsset;
use yii\web\YiiAsset;
use yii\widgets\PjaxAsset;

class ModuleTest extends TestCase
{
    public function testTheViewsAreStillResolvedInTheBundle(): void
    {
        self::assertSame(Yii::getAlias('@yii/debug/views'), $this->createModule()->getViewPath());
    }

    public function testJqueryIsPublishedForThePanels(): void
    {
        $bundles = $this->resetGlobalSettings($this->createModule());

        self::assertSame([
            'sourcePath' => '@vendor/components/jquery',
            'js' => ['jquery.min.js'],
            'publishOptions' => ['only' => ['jquery.min.js']],
        ], $bundles[JqueryAsset::class]);

        self::assertSame(['class' => EmptyAssetBundle::class], $bundles[PjaxAsset::class]);
        self::assertArrayNotHasKey(YiiAsset::class, $bundles);

        self::assertSame(\yii\web\View::class, Yii::$app->getView()::class);
    }

    public function testWithoutJqueryNothingDependingOnItIsPublished(): void
    {
        $bundles = $this->resetGlobalSettings($this->createModule(['jqueryPath' => null]));
        $empty = ['class' => EmptyAssetBundle::class];

        foreach ([JqueryAsset::class, YiiAsset::class, GridViewAsset::class, PjaxAsset::class] as $bundle) {
            self::assertSame($empty, $bundles[$bundle], $bundle);
        }

        // The panels' `POS_READY` scripts have nothing to run in, so the skeleton's own view still drops them.
        self::assertSame(View::class, Yii::$app->getView()::class);
    }

    public function testAMissingJqueryIsTreatedAsNone(): void
    {
        $bundles = $this->resetGlobalSettings($this->createModule(['jqueryPath' => '@vendor/components/nope']));

        self::assertSame(['class' => EmptyAssetBundle::class], $bundles[JqueryAsset::class]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createModule(array $config = []): Module
    {
        return new Module('debug', Yii::$app, $config);
    }

    /**
     * @return array<class-string, mixed>
     */
    private function resetGlobalSettings(Module $module): array
    {
        (new ReflectionMethod($module, 'resetGlobalSettings'))->invoke($module);

        $bundles = Yii::$app->getAssetManager()->bundles;
        self::assertIsArray($bundles);

        return $bundles;
    }
}
