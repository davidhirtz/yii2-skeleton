<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\data\ArrayDataProvider;

/**
 * The empty message is what a grid says it is *for*, so it may only replace the summary where the grid is empty
 * and nothing was searched for — a fruitless search needs the search summary instead, or the user is told the
 * grid is empty when it is their keyword that matched nothing.
 */
class GridSummaryTest extends TestCase
{
    private const string EMPTY_MESSAGE = 'This is what the grid is for.';

    public function testTheEmptyMessageReplacesTheEmptySummary(): void
    {
        self::assertStringContainsString(self::EMPTY_MESSAGE, $this->render([]));
    }

    public function testTheEmptyMessageIsNotRenderedForAFilledGrid(): void
    {
        $html = $this->render([new Model()]);

        self::assertStringNotContainsString(self::EMPTY_MESSAGE, $html);
        self::assertStringContainsString('Displaying the only record.', $html);
    }

    public function testTheEmptyMessageIsNotRenderedForAFruitlessSearch(): void
    {
        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;

        $this->getWebRequest()->setQueryParams(['q' => 'needle']);

        $html = $this->render([]);

        self::assertStringNotContainsString(self::EMPTY_MESSAGE, $html);
        self::assertStringContainsString('needle', $html);
    }

    /**
     * @param list<Model> $models
     */
    private function render(array $models): string
    {
        $grid = GridSummaryTestGridView::make()
            ->provider(new ArrayDataProvider(['allModels' => $models]));

        return (string)GridSummary::make()
            ->grid($grid)
            ->emptyMessage(self::EMPTY_MESSAGE);
    }
}

/**
 * @extends GridView<Model>
 */
class GridSummaryTestGridView extends GridView
{
}
