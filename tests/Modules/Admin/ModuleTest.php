<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Yii;

class ModuleTest extends TestCase
{
    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->setModule('test', ['class' => TestModule::class]);

        $this->module = $module;
    }

    public function testNavBarItems(): void
    {
        $nav = $this->module->aside(Nav::make());
        self::assertStringContainsString('Test Module', (string)$nav);
    }

    public function testDashboardPanels(): void
    {
        $nav = $this->module->dashboard(Dashboard::make());
        self::assertStringContainsString('Test Module', (string)$nav);
    }
}

class TestModule extends \Hirtz\Skeleton\Base\Module implements ModuleInterface
{
    public function aside(Nav $nav): Nav
    {
        return $nav->addItem(NavItem::make()->label('Test Module'));
    }

    public function dashboard(Dashboard $dashboard): Dashboard
    {
        return $dashboard->addItems(DashboardItem::make()
            ->label('Test Module')
            ->url(['/admin/system/test']));
    }
}
