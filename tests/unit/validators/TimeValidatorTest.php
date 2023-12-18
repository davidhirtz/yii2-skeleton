<?php

namespace davidhirtz\yii2\skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\validators\TimeValidator;
use yii\base\Model;

class TimeValidatorTest extends Unit
{
    public function testDefaultCurrencyAttribute()
    {
        $model = new class() extends Model {
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
        $this->assertTrue($model->validate());
        $this->assertEquals('01:00:00', $model->time);

        $model->time = '22';
        $this->assertTrue($model->validate());
        $this->assertEquals('22:00:00', $model->time);

        $model->time = '1:00';
        $this->assertTrue($model->validate());
        $this->assertEquals('01:00:00', $model->time);

        $model->time = '1:23';
        $this->assertTrue($model->validate());
        $this->assertEquals('01:23:00', $model->time);

        $model->time = '1:00 am';
        $this->assertTrue($model->validate());
        $this->assertEquals('01:00:00', $model->time);

        $model->time = '1:00 pm';
        $this->assertTrue($model->validate());
        $this->assertEquals('13:00:00', $model->time);

        $model->time = '10:00 pm';
        $this->assertTrue($model->validate());
        $this->assertEquals('22:00:00', $model->time);

        $model->time = '24:01';
        $this->assertFalse($model->validate());

        $model->time = 'invalid';
        $this->assertFalse($model->validate());
    }
}
