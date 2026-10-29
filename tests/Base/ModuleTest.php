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
     * A project's module lives in no `src/` tree, where the walk up to one used to answer a path above the filesystem
     * root.
     */
    public function testAModuleOutsideASourceTreeTakesTheApplicationViews(): void
    {
        $module = new ProjectModule('project');
        self::assertSame(Yii::getAlias('@views/project'), $module->getViewPath());
    }

    /** Yii derives the directory from the controller namespace through an alias only an installed extension has. */
    public function testAModuleTakesItsControllerPathFromItsBasePath(): void
    {
        $module = new ProjectModule('project');

        self::assertSame(__NAMESPACE__ . '\\Controllers', $module->controllerNamespace);
        self::assertSame('/project/app/Modules/Project/Controllers', $module->getControllerPath());
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
