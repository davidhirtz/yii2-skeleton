<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\ModuleInterface;
use davidhirtz\yii2\skeleton\widgets\panels\DashboardItem;
use davidhirtz\yii2\skeleton\widgets\panels\DashboardPanel;
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
            'module' => new DashboardPanel('Test Module'),
            'skeleton' => new DashboardPanel(
                items: [
                    'user' => new DashboardItem('Overridden label'),
                    'account' => new DashboardItem(url: ['/admin/account/test']),
                    'system' => new DashboardItem(roles: ['test']),
                    'homepage' => new DashboardItem(attributes: ['class' => 'test-class']),
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
