<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Panels;

use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ContentHealthPanel;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;

class DashboardTest extends TestCase
{
    public function testAPanelWithoutBodyRendersNothing(): void
    {
        self::assertSame('', DashboardPanel::make()->title('Empty')->render());

        $html = DashboardPanel::make()
            ->title('Title')
            ->content('Body')
            ->footer('More', '/more')
            ->render();

        self::assertStringContainsString('dashboard-panel', $html);
        self::assertStringContainsString('Body', $html);
        self::assertStringContainsString('<a href="/more">More</a>', $html);
    }

    public function testPanelsRenderInOrderInTheirOwnGrid(): void
    {
        $html = Dashboard::make()
            ->addPanel(
                second: DashboardPanel::make()->order(2)->content('Second panel'),
                first: DashboardPanel::make()->order(1)->content('First panel'),
            )
            ->render();

        self::assertStringContainsString('<div class="dashboard-panels">', $html);
        self::assertLessThan(strpos($html, 'Second panel'), strpos($html, 'First panel'));
    }

    public function testNoPanelGridWithoutAVisiblePanel(): void
    {
        $html = Dashboard::make()
            ->addPanel(DashboardPanel::make()->content('Hidden')->visible(false))
            ->render();

        self::assertStringNotContainsString('dashboard-panels', $html);
    }

    public function testTheAdminModuleAddsTheContentHealthPanelByName(): void
    {
        $dashboard = Dashboard::make();
        $dashboard->render();

        self::assertInstanceOf(ContentHealthPanel::class, $dashboard->getPanel('health'));
        self::assertNull($dashboard->getPanel('missing'));
    }

    public function testAContentHealthCheckCountingNothingIsLeftOut(): void
    {
        $panel = ContentHealthPanel::make()
            ->addCheck('none', fn (): int => 0, fn (int $count): string => "$count none", '/none')
            ->addCheck('some', fn (): int => 3, fn (int $count): string => "$count some", '/some');

        $html = $panel->render();

        self::assertStringContainsString('3 some', $html);
        self::assertStringNotContainsString('none', $html);
        self::assertSame('', ContentHealthPanel::make()
            ->addCheck('none', fn (): int => 0, fn (int $count): string => '', '/none')
            ->render());
    }

    public function testAContentHealthCheckBehindARoleIsNotCountedForAGuest(): void
    {
        $counted = false;

        $html = ContentHealthPanel::make()
            ->addCheck('secret', function () use (&$counted): int {
                $counted = true;
                return 1;
            }, fn (int $count): string => 'secret', '/secret', ['role-the-guest-lacks'])
            ->render();

        self::assertSame('', $html);
        self::assertFalse($counted);
    }
}
