<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\validators\CurrencyValidator;
use Yii;
use yii\base\Model;

class CurrencyValidatorTest extends Unit
{
    public function testDefaultCurrencyAttribute()
    {
        $model = new CurrencyValidatorTestModel();

        $model->currency = 10;
        self::assertTrue($model->validate());
        self::assertEquals('10.00', $model->currency);

        $model->currency = 10.00;
        self::assertTrue($model->validate());
        self::assertEquals('10.00', $model->currency);

        $model->currency = '10.00';
        self::assertTrue($model->validate());
        self::assertEquals('10.00', $model->currency);
    }

    public function testLocalizedCurrencyAttribute()
    {
        Yii::$app->language = 'de';
        $model = new CurrencyValidatorTestModel();

        $model->currency = '10,00';
        self::assertTrue($model->validate());
        self::assertEquals('10.00', $model->currency);
    }
}

class CurrencyValidatorTestModel extends Model
{
    public string|float|int|null $currency = null;

    public function rules(): array
    {
        return [
            [
                'currency',
                CurrencyValidator::class,
            ],
        ];
    }
}
