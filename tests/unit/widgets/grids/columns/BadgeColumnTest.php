<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\modules\admin\widgets\grids\columns;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\widgets\grids\columns\BadgeColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Yii;
use yii\base\Model;
use yii\web\Controller;

class BadgeColumnTest extends Unit
{
    public function testDefaultOptions(): void
    {
        $model = new class () extends Model {
            public ?int $count = 100;
        };

        $column = $this->createCounterColumn();

        $expects = '<td class="text-center"><div class="badge">100</div></td>';
        self::assertEquals($expects, (string)$column->renderBody($model, 0, 0));

        $column = $this->createCounterColumn()
            ->hiddenForSmallDevices();

        $model->count = 1000;

        $expects = '<td class="text-center hidden md:table-cell"><div class="badge">1,000</div></td>';
        self::assertEquals($expects, (string)$column->renderBody($model, 0, 0));

        $column = $this->createCounterColumn()
            ->hiddenForMediumDevices();

        $model->count = 0;

        $expects = '<td class="text-center hidden lg:table-cell"></td>';
        self::assertEquals($expects, (string)$column->renderBody($model, 0, 0));
    }

    public function testRouteAttribute(): void
    {
        Yii::$app->controller = $this->createMock(Controller::class);

        $model = new class () extends Model {
            public int $id = 1;
            public int $count = 10;
        };

        $column = $this->createCounterColumn()
            ->url(fn ($model) => ['view', 'id' => $model->id]);

        $expects = '<td class="text-center"><a class="badge" href="/view?id=1">10</a></td>';
        self::assertEquals($expects, (string)$column->renderBody($model, 0, 0));
    }

    protected function createCounterColumn(): BadgeColumn
    {
        return BadgeColumn::make()
            ->property('count')
            ->grid($this->createMock(GridView::class));
    }
}
