<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridSearchForm;
use Yii;
use yii\base\Model;
use yii\base\Module;
use yii\data\ArrayDataProvider;

/**
 * A GET form submits its own fields and nothing else, so the parameters the grid's route carries have to be
 * hidden inputs — the browser replaces the action's query string with the form's fields, and htmx strips it
 * from a boosted form for the same reason.
 */
class GridSearchFormTest extends TestCase
{
    public function testTheRouteParametersAreHiddenInputs(): void
    {
        $html = $this->render(['entry' => '1', 'category' => '2']);

        self::assertStringContainsString('action="/test" method="get"', $html);
        self::assertStringContainsString('<input type="hidden" name="entry" value="1">', $html);
        self::assertStringContainsString('<input type="hidden" name="category" value="2">', $html);
    }

    public function testTheSearchAndPageParametersAreNotRepeated(): void
    {
        $html = $this->render(['entry' => '1', 'q' => 'needle', 'page' => '2']);

        self::assertStringContainsString('<input type="hidden" name="entry" value="1">', $html);
        self::assertStringNotContainsString('name="page"', $html);
        self::assertStringNotContainsString('type="hidden" name="q"', $html);
        self::assertStringContainsString('name="q" value="needle"', $html);
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function render(array $queryParams): string
    {
        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;

        $this->getWebRequest()->setQueryParams($queryParams);

        $grid = GridSearchFormTestGridView::make()
            ->provider(new ArrayDataProvider(['allModels' => []]));

        return (string)GridSearchForm::make()->grid($grid);
    }
}

/**
 * @extends GridView<Model>
 */
class GridSearchFormTestGridView extends GridView
{
}
