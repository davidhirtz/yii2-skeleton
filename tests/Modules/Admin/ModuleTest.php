<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Config\DashboardItem;
use Hirtz\Skeleton\Modules\Admin\Config\MainMenuItemConfig;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class ModuleTest extends TestCase
{
    public function testNavBarItems(): void
    {
        $module = $this->getAdminModule();

        $module->setMainMenuItems([
            'test' => new MainMenuItemConfig(
                label: 'Test',
            ),
        ]);

        $items = array_keys($module->getMainMenuItems());

        self::assertEquals('users', current($items));
        self::assertEquals('test', end($items));

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        $items = array_keys($module->getMainMenuItems());

        self::assertContains('users', $items);
        self::assertContains('module', $items);
        self::assertContains('test', $items);
    }

    public function testDashboardPanels(): void
    {
        $module = $this->getAdminModule();

        $module->setModule('test', [
            'class' => TestModule::class,
        ]);

        $panels = $module->getDashboardPanels();
        $ids = array_keys($panels);

        self::assertEquals('skeleton', current($ids));
        self::assertEquals('module', end($ids));
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

    public function getMainMenuItems(): array
    {
        return [
            'module' => new MainMenuItemConfig(
                label: 'Test Module',
            ),
        ];
    }
}
