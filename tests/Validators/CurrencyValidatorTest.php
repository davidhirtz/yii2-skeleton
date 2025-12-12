<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\CurrencyValidator;
use Override;
use Yii;
use yii\base\Model;

class CurrencyValidatorTest extends TestCase
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

    #[Override]
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
