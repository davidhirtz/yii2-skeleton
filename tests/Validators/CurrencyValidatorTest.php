<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\CurrencyValidator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideSpaceGroupedValues(): array
    {
        return [
            'fr decimals' => ['fr', '10,00', '10.00'],
            'fr no-break space before the symbol' => ['fr', "10,00\u{00A0}€", '10.00'],
            'fr space before the symbol' => ['fr', '10 €', '10.00'],
            'fr narrow no-break space grouping' => ['fr', "1\u{202F}000,50 €", '1000.50'],
            'fr space grouping' => ['fr', '1 000,50', '1000.50'],
            'pt-PT no-break space grouping' => ['pt-PT', "1\u{00A0}000,50 €", '1000.50'],
        ];
    }

    #[DataProvider('provideSpaceGroupedValues')]
    public function testSpaceGroupedCurrencyAttribute(string $language, string $value, string $expected): void
    {
        Yii::$app->language = $language;
        Yii::$app->getFormatter()->currencyCode = 'EUR';

        $model = new CurrencyValidatorTestModel();
        $model->currency = $value;
        self::assertTrue($model->validate(), (string)$model->getFirstError('currency'));
        self::assertEquals($expected, $model->currency);
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
