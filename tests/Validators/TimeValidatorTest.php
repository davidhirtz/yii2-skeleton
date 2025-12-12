<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\TimeValidator;
use yii\base\Model;

class TimeValidatorTest extends TestCase
{
    public function testDefaultCurrencyAttribute(): void
    {
        $model = new class () extends Model {
            public ?string $time = null;

            public function rules(): array
            {
                return [
                    [
                        'time',
                        TimeValidator::class,
                    ],
                ];
            }
        };

        $model->time = '1';
        self::assertTrue($model->validate());
        self::assertEquals('01:00:00', $model->time);

        $model->time = '22';
        self::assertTrue($model->validate());
        self::assertEquals('22:00:00', $model->time);

        $model->time = '1:00';
        self::assertTrue($model->validate());
        self::assertEquals('01:00:00', $model->time);

        $model->time = '1:23';
        self::assertTrue($model->validate());
        self::assertEquals('01:23:00', $model->time);

        $model->time = '1:00 am';
        self::assertTrue($model->validate());
        self::assertEquals('01:00:00', $model->time);

        $model->time = '1:00 pm';
        self::assertTrue($model->validate());
        self::assertEquals('13:00:00', $model->time);

        $model->time = '10:00 pm';
        self::assertTrue($model->validate());
        self::assertEquals('22:00:00', $model->time);

        $model->time = '24:01';
        self::assertFalse($model->validate());

        $model->time = 'invalid';
        self::assertFalse($model->validate());
    }
}
