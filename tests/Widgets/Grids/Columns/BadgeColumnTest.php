<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Grids\Columns;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Yii;
use yii\base\Model;
use yii\base\Module;

class BadgeColumnTest extends TestCase
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
        /** @var Controller<Module> $controller */
        $controller = new Controller('test', Yii::$app);
        Yii::$app->controller = $controller;

        $model = new class () extends Model {
            public int $id = 1;
            public int $count = 10;
        };

        $column = $this->createCounterColumn()
            ->url(fn ($model) => ['view', 'id' => $model->id]);

        $expects = '<td class="text-center"><a class="badge" href="/test/view?id=1">10</a></td>';
        self::assertEquals($expects, (string)$column->renderBody($model, 0, 0));
    }

    protected function createCounterColumn(): BadgeColumn
    {
        return BadgeColumn::make()
            ->property('count')
            ->grid(GridView::make());
    }
}
