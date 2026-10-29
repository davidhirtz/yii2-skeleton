<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\data\ArrayDataProvider;

/**
 * The empty message replaces the summary only where nothing was searched for, or a fruitless search reads as an
 * empty grid.
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

    public function testALastPageWithOneRecordIsNotTheOnlyRecord(): void
    {
        $this->getWebRequest()->setQueryParams(['page' => 2]);

        $grid = GridSummaryTestGridView::make()->provider(new ArrayDataProvider([
            'allModels' => array_fill(0, 21, new Model()),
            'pagination' => ['pageSize' => 20],
        ]));

        self::assertStringNotContainsString('the only record', (string)GridSummary::make()->grid($grid));
    }

    public function testTheEmptyMessageIsNotRenderedForAFilterThatEmptiedTheGrid(): void
    {
        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;

        $this->getWebRequest()->setQueryParams(['colour' => 'green']);

        $grid = GridSummaryTestGridView::make()
            ->header([FilterDropdown::make()->paramName('colour')->items(['green' => 'Grün'])])
            ->provider(new ArrayDataProvider(['allModels' => []]));

        self::assertStringNotContainsString(self::EMPTY_MESSAGE, (string)GridSummary::make()->grid($grid)->emptyMessage(self::EMPTY_MESSAGE));
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
