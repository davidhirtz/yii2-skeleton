<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\widgets\grids\columns;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\widgets\grids\columns\BadgeColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Yii;
use yii\base\Model;
use yii\web\Controller;

class CounterColumnTest extends Unit
{
    public function testDefaultOptions(): void
    {
        $column = $this->createCounterColumn();

        $model = new class() extends Model {
            public ?int $count = 100;
        };

        $expects = '<td class="d-none d-md-table-cell text-center"><div class="badge">100</div></td>';
        self::assertEquals($expects, $column->renderBody($model, 0, 0));

        $model->count = 1000;

        $expects = '<td class="d-none d-md-table-cell text-center"><div class="badge">1,000</div></td>';
        self::assertEquals($expects, $column->renderBody($model, 0, 0));

        $model->count = 0;

        $expects = '<td class="d-none d-md-table-cell text-center"></td>';
        self::assertEquals($expects, $column->renderBody($model, 0, 0));
    }

    public function testRouteAttribute(): void
    {
        Yii::$app->controller = $this->createMock(Controller::class);

        $model = new class() extends Model {
            public int $id = 1;
            public int $count = 10;
        };

        $column = $this->createCounterColumn([
            'route' => fn ($model) => ['view', 'id' => $model->id],
        ]);

        $expects = '<td class="d-none d-md-table-cell text-center"><a class="badge" href="/view?id=1">10</a></td>';
        self::assertEquals($expects, $column->renderBody($model, 0, 0));

        $column = $this->createCounterColumn([
            'route' => '/static',
        ]);

        $expects = '<td class="d-none d-md-table-cell text-center"><a class="badge" href="/static">10</a></td>';
        self::assertEquals($expects, $column->renderBody($model, 0, 0));
    }

    protected function createCounterColumn(array $options = []): BadgeColumn
    {
        /** @var BadgeColumn $column */
        $column = Yii::createObject([
            'class' => BadgeColumn::class,
            'attribute' => 'count',
            'grid' => $this->createMock(GridView::class),
            ...$options
        ]);

        return $column;
    }
}
