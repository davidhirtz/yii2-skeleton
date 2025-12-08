<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use Hirtz\Skeleton\validators\CurrencyValidator;
use Yii;
use yii\base\Model;

class CurrencyValidatorTest extends Unit
{
    public function testDefaultCurrencyAttribute(): void
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

    public function testLocalizedCurrencyAttribute(): void
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

    #[\Override]
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
