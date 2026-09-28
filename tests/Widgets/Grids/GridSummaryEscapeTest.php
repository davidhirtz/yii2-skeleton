<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Web\Controller;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * The summary is markup, and the search comes from the URL.
 */
class GridSummaryEscapeTest extends TestCase
{
    public function testTheSearchIsEscaped(): void
    {
        Yii::$app->controller = new Controller('test', Yii::$app);
        $this->getWebRequest()->setQueryParams(['q' => '<img src=x onerror=alert(1)>']);

        $html = GridView::make()->provider(new ArrayDataProvider(['allModels' => []]))->render();

        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
