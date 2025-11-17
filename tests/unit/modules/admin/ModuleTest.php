<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\modules\admin\config\DashboardItemConfig;
use davidhirtz\yii2\skeleton\modules\admin\config\DashboardPanelConfig;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\ModuleInterface;
use Yii;

class ModuleTest extends Unit
{
    public function testNavBarItems(): void
    {
        $module = $this->getAdminModule();

        $module->setNavBarItems([
            'test' => [
                'name' => 'Test',
            ],
        ]);

        self::assertEquals(['users', 'test'], array_keys($module->getNavBarItems()));

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        self::assertEquals(['users', 'module', 'test'], array_keys($module->getNavBarItems()));
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

class TestModule extends \davidhirtz\yii2\skeleton\base\Module implements ModuleInterface
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

    public function getNavBarItems(): array
    {
        return [
            'module' => [
                'name' => 'Test Module',
            ],
        ];
    }
}
