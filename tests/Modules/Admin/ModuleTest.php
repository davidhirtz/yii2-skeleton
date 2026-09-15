<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\MainMenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SystemNavItem;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Hirtz\Skeleton\Widgets\Widget;
use Yii;
use yii\base\Event;

class ModuleTest extends TestCase
{
    use UserFixtureTrait;

    protected Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        // The admin menu is a logged-in surface: `DashboardNavItem` and `SystemNavItem` are guarded by `ROLE_AUTHENTICATED`.
        $this->getWebUser()->login($this->getUserFromFixture('admin'));

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $module->setModule('test', ['class' => TestModule::class]);

        $this->module = $module;
    }

    public function testNavBarItems(): void
    {
        $content = MainMenu::make()->render();

        self::assertStringContainsString('class="aside-main aside-nav nav"', $content);
        self::assertStringContainsString('Test Module', $content);
    }

    /**
     * `YII_DEBUG` is off in production only, so the error view is never rendered by a test — and a path that has
     * gone stale is only noticed on a live site.
     */
    public function testErrorView(): void
    {
        self::assertFileExists(Yii::getAlias(Module::ERROR_VIEW));
    }

    public function testDashboard(): void
    {
        self::assertStringContainsString('Test Module', Dashboard::make()->render());
    }

    public function testConfigureEventOnMainMenu(): void
    {
        Event::on(MainMenu::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var MainMenu $menu */
            $menu = $event->sender;
            $menu->addItem(NavItem::make()
                ->label('Main Menu Listener')
                ->url(['/admin/system/index']));
        });

        self::assertStringContainsString('Main Menu Listener', MainMenu::make()->render());
    }

    public function testConfigureEventOnSystemNavItem(): void
    {
        Event::on(SystemNavItem::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var SystemNavItem $item */
            $item = $event->sender;
            $item->addItem(NavItem::make()
                ->label('System Listener')
                ->url(['/admin/system/index']));
        });

        self::assertStringContainsString('System Listener', MainMenu::make()->render());
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
        return $dashboard->addItem(DashboardItem::make()
            ->label('Test Module')
            ->url(['/admin/system/test']));
    }
}
