<?php

namespace davidhirtz\yii2\skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\validators\HexColorValidator;
use yii\base\Model;

class HexColorValidatorTest extends Unit
{
    public function testHexColorAttribute()
    {
        $model = new class() extends Model {
            public ?string $color = null;

            public function rules(): array
            {
                return [
                    [
                        'color',
                        HexColorValidator::class,
                    ],
                ];
            }
        };

        $model->color = 'fff';
        self::assertTrue($model->validate());

        $model->color = '000000';
        self::assertTrue($model->validate());

        $model->color = 'invalid';
        self::assertFalse($model->validate());
    }
}
