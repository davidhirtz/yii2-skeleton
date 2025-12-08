<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\unit\modules\admin;

use Codeception\Test\Unit;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardItemConfig;
use Hirtz\Skeleton\Modules\Admin\Config\DashboardPanelConfig;
use Hirtz\Skeleton\Modules\Admin\Config\MainMenuItemConfig;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Yii;

class ModuleTest extends Unit
{
    public function testNavBarItems(): void
    {
        $module = $this->getAdminModule();

        $module->setMainMenuItems([
            'test' => new MainMenuItemConfig(
                label: 'Test',
            ),
        ]);

        self::assertEquals(['users', 'test'], array_keys($module->getMainMenuItems()));

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        self::assertEquals(['users', 'module', 'test'], array_keys($module->getMainMenuItems()));
    }

    public function testDashboardPanels(): void
    {
        $module = $this->getAdminModule();

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        $panels = $module->getDashboardPanels();

        self::assertEquals(['skeleton', 'module'], array_keys($panels));
        self::assertEquals('Overridden label', $panels['skeleton']->items['user']->label);
        self::assertEquals(['/admin/account/test'], $panels['skeleton']->items['account']->url);
        self::assertContains('test', $panels['skeleton']->items['system']->roles);
        self::assertEquals('test-class', $panels['skeleton']->items['homepage']->attributes['class'] ?? '');

        $module->setDashboardPanels([
            'trail' => null,
        ]);

        self::assertArrayNotHasKey('trail', $panels);
    }

    private function getAdminModule(): Module
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        return $module;
    }
}

class TestModule extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
{
    public function getDashboardPanels(): array
    {
        return [
            'module' => new DashboardPanelConfig('Test Module'),
            'skeleton' => new DashboardPanelConfig(
                items: [
                    'user' => new DashboardItemConfig('Overridden label'),
                    'account' => new DashboardItemConfig(url: ['/admin/account/test']),
                    'system' => new DashboardItemConfig(roles: ['test']),
                    'homepage' => new DashboardItemConfig(attributes: ['class' => 'test-class']),
                ]
            ),
        ];
    }

    public function getMainMenuItems(): array
    {
        return [
            'module' => new MainMenuItemConfig(
                label: 'Test Module',
            ),
        ];
    }
}
