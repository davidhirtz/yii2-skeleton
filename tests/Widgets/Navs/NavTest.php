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

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span class="nav-link-label">Home</span></a></li></ul>', $content);
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
        $this->getWebUser()->disableRbacForGuests = false;

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

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span class="nav-link-label">Home</span></a></li></ul>', $content);
    }

    public function testAuthenticatedItemRole(): void
    {
        $nav = fn (): string => Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/')
                ->roles([User::ROLE_AUTHENTICATED]))
            ->render(true);

        self::assertEmpty($nav());

        $this->getWebUser()->login($this->getUserFromFixture('admin'));

        self::assertStringContainsString('Home', $nav());
    }

    public function testAParentIsShownForTheRoleOfASubItemAndLeadsToIt(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, User::AUTH_USER_ASSIGN);
        $this->getWebUser()->setIdentity($user);

        $content = NavItem::make()
            ->label('Parent')
            ->url('/parent')
            ->roles([User::AUTH_USER])
            ->addItem(
                NavItem::make()->label('Open')->url('/open')->order(2)->roles([User::AUTH_USER_ASSIGN]),
                NavItem::make()->label('Inherits')->url('/inherits')->order(1),
                NavItem::make()->label('Closed')->url('/closed')->order(0)->roles([User::AUTH_USER]),
            )
            ->render();

        self::assertStringStartsWith('<li class="nav-item"><a class="nav-link" href="/open"><span class="nav-link-label">Parent</span></a>', $content);
        self::assertStringContainsString('<span class="nav-link-label">Open</span>', $content);
        self::assertStringNotContainsString('<span class="nav-link-label">Closed</span>', $content);
    }

    public function testAParentIsNotShownForASubItemWithoutRoles(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $content = NavItem::make()
            ->label('Parent')
            ->url('/parent')
            ->roles([User::AUTH_USER])
            ->addItem(NavItem::make()->label('Inherits')->url('/inherits'))
            ->render();

        self::assertSame('', $content);
    }

    public function testItemBadgeAndIcon(): void
    {
        $content = Nav::make()
            ->addItem(NavItem::make()
                ->label('Home')
                ->url('/')
                ->badge(fn (Badge $badge) => $badge->value('New')->class('badge'))
                ->icon(fn (Icon $icon) => $icon->name('home')->addClass('hidden'))
                ->link(fn (A $link) => $link->addClass('home'))
                ->link(fn (A $link) => $link->addClass('start')))
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active home start" href="/"><span class="hidden nav-link-icon fas fa-home"></span><span class="nav-link-label">Home</span><span class="badge">New</span></a></li></ul>', $content);
    }

    public function testActiveItemFromUrl(): void
    {
        $content = Nav::make()
            ->items([
                NavItem::make()->label('Home')->url('/'),
                NavItem::make()->label('Test')->url(['site/test']),
            ])
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link" href="/site/test"><span class="nav-link-label">Test</span></a></li></ul>', $content);
    }

    /**
     * A URL manager adding a language prefix makes every link differ from the URL the request came in on.
     */
    public function testActiveItemFromTheCurrentRouteWhenTheUrlDiffers(): void
    {
        Yii::$app->controller = new class ('test', Yii::$app) extends Controller {
            public function getRoute(): string
            {
                return 'site/test';
            }
        };

        $this->getWebRequest()->setUrl('/de/site/test');

        $content = Nav::make()
            ->items([
                NavItem::make()->label('Home')->url('/'),
                NavItem::make()->label('Test')->url(['/site/test']),
            ])
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span class="nav-link-label">Test</span></a></li></ul>', $content);
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

        $needle = '<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span class="nav-link-label">Test</span></a></li></ul>';
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

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/"><span class="nav-link-label">Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithRequestQueryParameters(): void
    {
        $this->getWebRequest()->setQueryParams(['id' => 1]);

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

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span class="nav-link-label">Test</span></a></li></ul>', $content);
    }

    public function testActiveItemWithAddedRoute(): void
    {
        $this->getWebRequest()->setQueryParams(['id' => 1]);

        $content = Nav::make()
            ->items([
                NavItem::make()
                    ->label('Home')
                    ->url(['site/test'])
                    ->addRoute('site/index', ['id' => '2']),
                NavItem::make()
                    ->label('Test')
                    ->url(['site/test'])
                    ->addRoute('site/other')
                    ->addRoute('site/index', ['id']),
            ])
            ->render();

        self::assertStringContainsString('<li class="nav-item"><a class="nav-link" href="/site/test"><span class="nav-link-label">Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span class="nav-link-label">Test</span></a></li>', $content);
    }

    public function testNamedItemIsReadByName(): void
    {
        $nav = Nav::make()->addItem(
            home: NavItem::make()
                ->label('Home')
                ->url(['site/test']),
        );

        $nav->getItem('home')?->addRoute('site/index');

        self::assertNull($nav->getItem('about'));
        self::assertStringContainsString('<a class="nav-link active" href="/site/test">', $nav->render());
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

        self::assertStringContainsString('<span class="nav-link-label">Home</span>', $content);
        self::assertStringContainsString('<span class="nav-link-label">Contact</span>', $content);
        self::assertStringNotContainsString('<span class="nav-link-label">About</span>', $content);

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

        self::assertStringContainsString('<span class="nav-link-label">Home</span>', $content);
        self::assertStringNotContainsString('<span class="nav-link-label">About</span>', $content);
    }
}
