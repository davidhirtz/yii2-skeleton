<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Modules\Admin\ModuleInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use ReflectionMethod;
use Yii;

class ModuleTest extends TestCase
{
    use UserFixtureTrait;

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

    public function testSetLanguageFromIdentity(): void
    {
        User::updateAll(['language' => 'de'], ['id' => 1]);
        Yii::$app->getUser()->login(User::findOne(1));

        $this->setLanguage();

        self::assertEquals('de', Yii::$app->language);
    }

    public function testSetIdentityLanguageFromRequest(): void
    {
        Yii::$app->getUser()->login(User::findOne(1));
        Yii::$app->getRequest()->setQueryParams(['language' => 'de']);

        $this->setLanguage();

        self::assertEquals('de', User::findOne(1)->language);
        self::assertEmpty($this->getLanguageCookieValue());
    }

    public function testSetGuestCookieLanguageFromRequest(): void
    {
        Yii::$app->getRequest()->setQueryParams(['language' => 'de']);

        $this->setLanguage();

        self::assertEquals('de', $this->getLanguageCookieValue());
    }

    protected function setLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $method = new ReflectionMethod($this->module, 'setLanguage');
        $method->invoke($this->module, Yii::$app->getRequest());
    }

    protected function getLanguageCookieValue(): ?string
    {
        return Yii::$app->getResponse()->getCookies()->get(Yii::$app->getRequest()->languageParam)?->value;
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
