<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\FilterDropdown;
use Override;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\data\ArrayDataProvider;

/**
 * A grid with nothing in it and nothing narrowing it has nothing to search or filter, so its toolbar is dropped
 * rather than offered over an empty table — unless it is the toolbar that emptied it (monorepo issue #159).
 */
class GridHeaderTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;
    }

    public function testAnEmptyGridRendersNoToolbar(): void
    {
        $html = $this->render([]);

        self::assertStringNotContainsString('grid-header', $html);
        self::assertStringNotContainsString('name="q"', $html);
    }

    public function testAFilledGridRendersItsToolbar(): void
    {
        $html = $this->render([new Model()]);

        self::assertStringContainsString('grid-header', $html);
        self::assertStringContainsString('name="q"', $html);
    }

    public function testAFruitlessSearchKeepsTheToolbar(): void
    {
        $this->getWebRequest()->setQueryParams(['q' => 'needle']);

        $html = $this->render([]);

        self::assertStringContainsString('grid-header', $html);
        self::assertStringContainsString('needle', $html);
    }

    public function testAFruitlessFilterKeepsTheToolbar(): void
    {
        $this->getWebRequest()->setQueryParams(['colour' => 'green']);

        $html = $this->render([]);

        self::assertStringContainsString('grid-header', $html);
    }

    /**
     * @param list<Model> $models
     */
    private function render(array $models): string
    {
        return (string)GridHeaderTestGridView::make()
            ->provider(new ArrayDataProvider(['allModels' => $models]));
    }
}

/**
 * @extends GridView<Model>
 */
class GridHeaderTestGridView extends GridView
{
    #[Override]
    protected function configure(): void
    {
        $this->columns ??= [];

        $this->header ??= [
            FilterDropdown::make()
                ->label('Colour')
                ->paramName('colour')
                ->items(['green' => 'Green']),
            $this->getSearchInput(),
        ];

        parent::configure();
    }
}
