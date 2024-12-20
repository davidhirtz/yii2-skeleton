<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\console;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\traits\StdOutBufferControllerTrait;
use davidhirtz\yii2\skeleton\console\controllers\TrailController;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\tests\support\fixtures\TrailFixture;
use Yii;
use yii\base\InvalidConfigException;

class TrailControllerTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'trail' => [
                'class' => TrailFixture::class,
                'dataFile' => codecept_data_dir() . 'trail.php',
            ],
        ];
    }

    public function testActionUpdateModels(): void
    {
        Yii::$container->set('invalid\namespace\models\Model', Trail::class);
        $controller = $this->createTrailController();
        $controller->actionUpdateModels();

        self::assertEquals('Updated 1 davidhirtz\yii2\skeleton\models\Trail trail records' . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testActionClearWithoutOffset(): void
    {
        $controller = $this->createTrailController();

        $this->expectException(InvalidConfigException::class);
        $controller->actionClear();
    }

    public function testActionClear(): void
    {
        $controller = $this->createTrailController();

        $twoYears = 60 * 60 * 24 * 365 * 2;
        $controller->actionClear($twoYears);
        self::assertEquals('No expired trail records found' . PHP_EOL, $controller->flushStdOutBuffer());
        self::assertEquals(5, Trail::find()->count());

        $sevenMonths = 60 * 60 * 24 * 30 * 7;
        $controller->actionClear($sevenMonths);
        self::assertStringContainsString('Deleted 1 expired trail records', $controller->flushStdOutBuffer());
        self::assertEquals(4, Trail::find()->count());
    }

    public function testActionClearWithManyRecords(): void
    {
        $date = gmdate('Y-m-d H:i:s', strtotime('-2 years'));

        Trail::batchInsert(array_fill(0, 100, [
            'type' => Trail::TYPE_CREATE,
            'created_at' => $date,
        ]));

        self::assertEquals(105, Trail::find()->count());

        $controller = $this->createTrailController();
        $controller->sleep = 0;

        $controller->actionClear(1);
        self::assertStringContainsString('Deleted 105 expired trail records', $controller->flushStdOutBuffer());

        self::assertEmpty(Trail::find()->count());
    }

    public function testActionOptimize(): void
    {
        $controller = $this->createTrailController();
        $controller->actionOptimize();

        self::assertStringStartsWith('Optimizing trail table...  done', $controller->flushStdOutBuffer());
    }

    protected function createTrailController(): TrailControllerMock
    {
        return new TrailControllerMock('trail', Yii::$app);
    }
}

class TrailControllerMock extends TrailController
{
    use StdOutBufferControllerTrait;
}
