<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Pagers;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Pagers\LinkPager;
use yii\data\Pagination;

/**
 * An `hx-swap` naming no style falls back to htmx's `innerHTML`, so the response's `#wrap` landed inside the page's
 * and the page held two of them (monorepo issue #374).
 */
class LinkPagerTest extends TestCase
{
    public function testThePagerSwapsTheWrapItself(): void
    {
        $html = LinkPager::widget([
            'pagination' => new Pagination(['totalCount' => 100, 'pageSize' => 10, 'route' => 'admin/user/index']),
        ]);

        self::assertStringContainsString('hx-swap:inherited="outerHTML scroll:top"', $html);
    }

    public function testAButtonPushingHistorySwapsTheWrapItself(): void
    {
        $html = Button::make()->text('Next')->get(['/admin/user/index'])->render();

        self::assertStringContainsString('hx-swap="outerHTML show:top"', $html);
    }
}
