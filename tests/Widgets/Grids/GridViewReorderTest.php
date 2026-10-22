<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Override;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\data\ArrayDataProvider;

class GridViewReorderTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;
    }

    public function testAnUnsortedGridPostsItsOrderToTheReorderAction(): void
    {
        $html = $this->render(GridViewReorderTestGridView::make());

        self::assertStringContainsString('data-reorder-url="/test/reorder"', $html);
        self::assertStringContainsString('data-reorder-announcement="', $html);
    }

    public function testAPickerIsNeverReorderable(): void
    {
        $html = $this->render(GridViewReorderTestGridView::make()->picker());

        self::assertStringNotContainsString('data-reorder-url', $html);
    }

    public function testAGridSortedByItsColumnsIsNotReorderable(): void
    {
        $html = $this->render(GridViewReorderTestGridView::make(), ['attributes' => []]);

        self::assertStringNotContainsString('data-reorder-url', $html);
    }

    /**
     * @param array<string, mixed>|false $sort
     */
    private function render(GridViewReorderTestGridView $grid, array|false $sort = false): string
    {
        return $grid
            ->provider(new ArrayDataProvider([
                'allModels' => [new Model(), new Model()],
                'pagination' => false,
                'sort' => $sort,
            ]))
            ->render();
    }
}

/**
 * @extends GridView<Model>
 */
class GridViewReorderTestGridView extends GridView
{
    private bool $picker = false;

    public function picker(): static
    {
        $this->picker = true;
        return $this;
    }

    #[Override]
    protected function isPicker(): bool
    {
        return $this->picker;
    }
}
