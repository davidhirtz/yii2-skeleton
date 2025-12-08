<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\web;

use Codeception\Test\Unit;
use Hirtz\Skeleton\web\Controller;
use Yii;
use yii\base\Model;

class ControllerTest extends Unit
{
    public function testErrorOrSuccessWithModelHavingErrors(): void
    {
        $model = new Model();
        $model->addError('field', 'error');

        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess($model, 'Success message');
        $flashes = Yii::$app->getSession()->getAllFlashes();

        self::assertArrayHasKey('danger', $flashes);
        self::assertEquals('error', $flashes['danger'][0]['field']);
        self::assertArrayNotHasKey('success', $flashes);
    }

    public function testErrorOrSuccessWithModelWithoutErrors(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess(new Model(), 'Success message');
        $flashes = Yii::$app->getSession()->getAllFlashes();

        self::assertArrayHasKey('success', $flashes);
        self::assertEquals('Success message', $flashes['success'][0]);
        self::assertArrayNotHasKey('error', $flashes);
    }

    public function testErrorOrSuccessWithNonEmptyArray(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess(['error'], 'Success message');
        $flashes = Yii::$app->getSession()->getAllFlashes();

        codecept_debug($flashes);

        self::assertArrayHasKey('danger', $flashes);
        self::assertEquals(['error'], $flashes['danger'][0]);
        self::assertArrayNotHasKey('success', $flashes);
    }

    public function testErrorOrSuccessWithEmptyArray(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess([], 'Success message');
        $flashes = Yii::$app->getSession()->getAllFlashes();

        self::assertArrayHasKey('success', $flashes);
        self::assertEquals('Success message', $flashes['success'][0]);
        self::assertArrayNotHasKey('error', $flashes);
    }
}
