<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Panels;

use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\ContentHealthPanel;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Hirtz\Skeleton\Widgets\Panels\DashboardItem;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Hirtz\Skeleton\Widgets\Panels\ShortcutsPanel;
use Yii;

class DashboardTest extends TestCase
{
    public function testAPanelWithoutBodyRendersNothing(): void
    {
        self::assertSame('', DashboardPanel::make()->title('Empty')->render());

        $html = DashboardPanel::make()
            ->title('Title')
            ->content('Body')
            ->render();

        self::assertStringContainsString('dashboard-item', $html);
        self::assertStringContainsString('<span class="dashboard-item-title"><span>Title</span></span>', $html);
        self::assertStringContainsString('Body', $html);
    }

    public function testATitleWithAUrlIsALinkLeadByItsIcon(): void
    {
        $html = DashboardPanel::make()
            ->title('Å & 東京')
            ->icon('bolt')
            ->url('/more')
            ->content('Body')
            ->render();

        self::assertStringContainsString('<a class="dashboard-item-title" href="/more"><span class="dashboard-item-icon fas fa-bolt"></span><span>Å &amp; 東京</span></a>', $html);
    }

    public function testPanelsRenderInOrderInTheirOwnGrid(): void
    {
        $html = Dashboard::make()
            ->addPanel(
                second: DashboardPanel::make()->order(2)->content('Second panel'),
                first: DashboardPanel::make()->order(1)->content('First panel'),
            )
            ->render();

        self::assertStringContainsString('<div class="dashboard">', $html);
        self::assertLessThan(strpos($html, 'Second panel'), strpos($html, 'First panel'));
    }

    public function testNoGridWithoutAVisiblePanel(): void
    {
        $html = Dashboard::make()
            ->prepare(fn (Dashboard $dashboard) => $dashboard->panels(fn (): array => [
                DashboardPanel::make()->content('Hidden')->visible(false),
            ]))
            ->render();

        self::assertSame('', $html);
    }

    public function testTheItemsAreTheLinksOfTheShortcutsPanelInOrder(): void
    {
        $dashboard = Dashboard::make()->addItem(
            second: DashboardItem::make()->label('Second')->order(2)->url('/second'),
            first: DashboardItem::make()->label('First')->order(1)->url('/first'),
        );

        $html = $dashboard->render();

        self::assertInstanceOf(ShortcutsPanel::class, $dashboard->getPanel('shortcuts'));
        self::assertStringContainsString(Yii::t('skeleton', 'DASHBOARD_SHORTCUTS_TITLE'), $html);
        self::assertLessThan(strpos($html, 'href="/second"'), strpos($html, 'href="/first"'));
        self::assertSame('', ShortcutsPanel::make()->render());
    }

    public function testGoogleShortcutsNeedTheirParams(): void
    {
        self::assertStringNotContainsString('google.com', Dashboard::make()->render());

        Yii::$app->params['googleSearchConsoleProperty'] = 'sc-domain:example.com';
        Yii::$app->params['googleAnalyticsPropertyId'] = '123456';

        $html = Dashboard::make()->render();

        self::assertStringContainsString('href="https://search.google.com/search-console?resource_id=sc-domain%3Aexample.com"', $html);
        self::assertStringContainsString('href="https://analytics.google.com/analytics/web/#/p123456/reports/intelligenthome"', $html);
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

        self::assertStringContainsString('<span class="strong">3</span> <span>3 some</span>', $html);
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
