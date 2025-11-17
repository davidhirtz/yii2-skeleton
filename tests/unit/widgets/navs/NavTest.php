<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\widgets\navs;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\AssetDirectoryTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\web\Controller;
use davidhirtz\yii2\skeleton\widgets\navs\Nav;
use davidhirtz\yii2\skeleton\widgets\navs\NavItem;
use Yii;

/**
 * @property UnitTester $tester
 */
class NavTest extends Unit
{
    use AssetDirectoryTrait;

    protected function _before(): void
    {
        $this->createAssetDirectory();

        Yii::$app->controllerMap['site'] = TestSiteController::class;
        $this->tester->amOnRoute('site/index');

        parent::_before();
    }

    protected function _after(): void
    {
        $this->removeAssetDirectory();
        parent::_after();
    }

    public function testHideSingleItem(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url(['site/index']))
            ->render();

        self::assertEmpty($content);

        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url(['site/index']))
            ->showSingleItem()
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/site/index"><span>Home</span></a></li></ul>', $content);
    }

    public function testItemVisibility(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url(['site/index'])
                ->visible(false))
            ->render();

        self::assertEmpty($content);
    }

    public function testItemRoles(): void
    {
        Yii::$app->getUser()->disableRbacForGuests = false;

        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url(['site/index'])
                    ->roles(['*']),
                NavItem::make()
                    ->label('Test')
                    ->url(['/admin/dashboard/index'])
                    ->roles([User::AUTH_ROLE_ADMIN])
            )
            ->showSingleItem()
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/site/index"><span>Home</span></a></li></ul>', $content);
    }

    public function testItemBadgeAndIcon(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url(['site/index'])
                ->badge('New')
                ->badgeAttributes(['class' => 'badge'])
                ->icon('home')
                ->iconAttributes(['class' => 'hidden']))
            ->showSingleItem()
            ->render();


        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/site/index"><i class="hidden fas fa-home"></i><span>Home</span><span class="badge">New</span></a></li></ul>', $content);
    }

    public function testActiveItemFromUrl(): void
    {
        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url(['site/index']),
                NavItem::make()
                    ->label('Test')
                    ->url(['site/test'])
            )
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/site/index"><span>Home</span></a></li><li class="nav-item"><a class="nav-link" href="/site/test"><span>Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithRoutes(): void
    {
        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url(['site/test']),
                NavItem::make()
                    ->label('Test')
                    ->routes(['site/index'])
                    ->url(['site/test'])
            )
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span>Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithSkippedRoute(): void
    {
        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url(['site/index'])
                    ->routes(['!site/index']),
                NavItem::make()
                    ->label('Test')
                    ->url(['site/index'])
            )
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/index"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/index"><span>Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithRequestQueryParameters(): void
    {
        Yii::$app->getRequest()->setQueryParams(['id' => 1]);

        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url(['site/test'])
                    ->routes([
                        ['site/index', 'id' => 2],
                    ]),
                NavItem::make()
                    ->label('Test')
                    ->url(['site/test'])
                    ->routes([
                        ['site/index', 'id' => 1],
                    ])
            )
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span>Test</span></a></li></ul>', $content);
    }
}

class TestSiteController extends Controller
{
    public function actionIndex(): string
    {
        return '';
    }
}
