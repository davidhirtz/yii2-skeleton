<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Buttons\Badge;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class NavTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->controller = new class ('test', Yii::$app) extends Controller {
            public function getRoute(): string
            {
                return 'site/index';
            }
        };
    }

    public function testShowSingleItem(): void
    {
        $content = Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/'))
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li></ul>', $content);
    }

    public function testItemVisibility(): void
    {
        $content = Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/')
                ->visible(false))
            ->render();

        self::assertEmpty($content);
    }

    public function testItemRoles(): void
    {
        Yii::$app->getUser()->disableRbacForGuests = false;

        $content = Nav::make()
            ->items([
                NavItem::make()
                    ->label('Home')
                    ->url('/')
                    ->roles(['*']),
                NavItem::make()
                    ->label('Test')
                    ->url(['/admin/dashboard/index'])
                    ->roles([User::AUTH_ROLE_ADMIN])
            ])
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li></ul>', $content);
    }

    public function testAuthenticatedItemRole(): void
    {
        $nav = fn (): string => Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/')
                ->roles([NavItem::ROLE_AUTHENTICATED]))
            ->render(true);

        self::assertEmpty($nav());

        Yii::$app->getUser()->login($this->getUserFromFixture('admin'));

        self::assertStringContainsString('Home', $nav());
    }

    public function testItemBadgeAndIcon(): void
    {
        $content = Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/')
                ->badge(fn (Badge $badge) => $badge->value('New')->class('badge'))
                ->icon(fn (Icon $icon) => $icon->name('home')->addClass('hidden'))
                ->link(fn (A $link) => $link->addClass('home')))
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active home" href="/"><span class="hidden nav-link-icon fas fa-home"></span><span>Home</span><span class="badge">New</span></a></li></ul>', $content);
    }

    public function testActiveItemFromUrl(): void
    {
        $content = Nav::make()
            ->items([
                NavItem::make()->label('Home')->url('/'),
                NavItem::make()->label('Test')->url(['site/test']),
            ])
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li><li class="nav-item"><a class="nav-link" href="/site/test"><span>Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithRoutes(): void
    {
        $content = Nav::make()
            ->items([
                NavItem::make()
                    ->label('Home')
                    ->url(['site/test']),
                NavItem::make()
                    ->label('Test')
                    ->routes(['/'])
                    ->url(['site/test'])
            ])
            ->render();

        $needle = '<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span>Test</span></a></li></ul>';
        self::assertStringContainsString($needle, $content);
    }

    public function testActiveItemWithSkippedRoute(): void
    {
        $content = Nav::make()
            ->items([
                NavItem::make()
                    ->label('Home')
                    ->url('/')
                    ->routes(['!']),
                NavItem::make()
                    ->label('Test')
                    ->url('/')
            ])
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/"><span>Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithRequestQueryParameters(): void
    {
        Yii::$app->getRequest()->setQueryParams(['id' => 1]);

        $content = Nav::make()
            ->items([
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
                    ]),
            ])
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span>Test</span></a></li></ul>', $content);
    }

    public function testNamedItemIsReplacedAndRemoved(): void
    {
        $content = Nav::make()
            ->addItem(
                home: NavItem::make()
                    ->label('Home')
                    ->url('/'),
                about: NavItem::make()
                    ->label('About')
                    ->url('/about'),
            )
            ->addItem(about: NavItem::make()
                ->label('Contact')
                ->url('/contact'))
            ->render();

        self::assertStringContainsString('<span>Home</span>', $content);
        self::assertStringContainsString('<span>Contact</span>', $content);
        self::assertStringNotContainsString('<span>About</span>', $content);

        $content = Nav::make()
            ->addItem(
                home: NavItem::make()
                    ->label('Home')
                    ->url('/'),
                about: NavItem::make()
                    ->label('About')
                    ->url('/about'),
            )
            ->removeItem('about')
            ->render();

        self::assertStringContainsString('<span>Home</span>', $content);
        self::assertStringNotContainsString('<span>About</span>', $content);
    }
}
