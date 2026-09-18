<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Base;

use Hirtz\Skeleton\Base\Module;
use Hirtz\Skeleton\Modules\Admin\Module as AdminModule;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class ModuleTest extends TestCase
{
    public function testABundleModuleTakesTheViewsBesideItsSourceTree(): void
    {
        $module = Yii::$app->getModule('admin');
        self::assertInstanceOf(AdminModule::class, $module);

        self::assertSame(
            realpath(Yii::getAlias('@skeleton') . '/../resources/views/admin'),
            realpath($module->getViewPath()),
        );
    }

    public function testAConfiguredViewPathSurvivesInit(): void
    {
        $module = new AdminModule('admin', null, ['viewPath' => '@runtime/views']);
        self::assertSame(Yii::getAlias('@runtime/views'), $module->getViewPath());
    }

    /**
     * A project's own module class lives in no `src/` tree, so the loop walking up to one used to pop every
     * segment and answer a path above the filesystem root.
     */
    public function testAModuleOutsideASourceTreeTakesTheApplicationViews(): void
    {
        $module = new ProjectModule('project');
        self::assertSame(Yii::getAlias('@views/project'), $module->getViewPath());
    }
}

class ProjectModule extends Module
{
    #[Override]
    public function getBasePath(): string
    {
        return '/project/app/Modules/Project';
    }
}
