<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Yii;

class NavTest extends TestCase
{
    public function testHideSingleItem(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url('/'))
            ->render();

        self::assertEmpty($content);

        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url('/'))
            ->showSingleItem()
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li></ul>', $content);
    }

    public function testItemVisibility(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
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
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url('/')
                    ->roles(['*']),
                NavItem::make()
                    ->label('Test')
                    ->url(['/admin/dashboard/index'])
                    ->roles([User::AUTH_ROLE_ADMIN])
            )
            ->showSingleItem()
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li></ul>', $content);
    }

    public function testItemBadgeAndIcon(): void
    {
        $content = Nav::make()
            ->items(NavItem::make()
                ->label('Home')
                ->url('/')
                ->badge('New')
                ->badgeAttributes(['class' => 'badge'])
                ->icon('home')
                ->iconAttributes(['class' => 'hidden']))
            ->showSingleItem()
            ->render();


        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><i class="hidden fas fa-home"></i><span>Home</span><span class="badge">New</span></a></li></ul>', $content);
    }

    public function testActiveItemFromUrl(): void
    {
        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url('/'),
                NavItem::make()
                    ->label('Test')
                    ->url(['site/test'])
            )
            ->render();

        self::assertEquals('<ul class="nav"><li class="nav-item"><a class="nav-link active" href="/"><span>Home</span></a></li><li class="nav-item"><a class="nav-link" href="/site/test"><span>Test</span></a></li></ul>', $content);
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
                    ->routes(['/'])
                    ->url(['site/test'])
            )
            ->render();

        $expected = '<ul class="nav"><li class="nav-item"><a class="nav-link" href="/site/test"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/site/test"><span>Test</span></a></li></ul>';
        self::assertStringContainsString($expected, $content);
    }

    public function testActiveItemWithSkippedRoute(): void
    {
        $content = Nav::make()
            ->items(
                NavItem::make()
                    ->label('Home')
                    ->url('/')
                    ->routes(['!']),
                NavItem::make()
                    ->label('Test')
                    ->url('/')
            )
            ->render();

        self::assertStringContainsString('<ul class="nav"><li class="nav-item"><a class="nav-link" href="/"><span>Home</span></a></li><li class="nav-item"><a class="nav-link active" href="/"><span>Test</span></a></li></ul>', $content);
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
