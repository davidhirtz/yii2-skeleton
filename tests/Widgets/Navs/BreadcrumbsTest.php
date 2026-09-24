<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Navs;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Navs\Breadcrumbs;
use Yii;

class BreadcrumbsTest extends TestCase
{
    public function testTheHomeLinkIsNotBoosted(): void
    {
        Yii::$app->controller = new Controller('test', Yii::$app);

        $html = (string)Breadcrumbs::make()->addBreadcrumb('Page', ['/test/index']);

        self::assertSame(1, substr_count($html, 'hx-boost="false"'));
        self::assertMatchesRegularExpression('/<a[^>]+hx-boost="false"[^>]*>[^<]*\(Local\)</', $html);
    }
}
