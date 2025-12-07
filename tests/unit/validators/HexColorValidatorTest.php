<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\validators;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\validators\HexColorValidator;
use yii\base\Model;

class HexColorValidatorTest extends Unit
{
    public function testHexColorAttribute(): void
    {
        $model = new class () extends Model {
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
        self::assertEquals('#fff', $model->color);

        $model->color = '#fff';
        self::assertTrue($model->validate());
        self::assertEquals('#fff', $model->color);

        $model->color = '000000';
        self::assertTrue($model->validate());
        self::assertEquals('#000000', $model->color);

        $model->color = '#000000';
        self::assertTrue($model->validate());
        self::assertEquals('#000000', $model->color);

        $model->color = 'invalid';
        self::assertFalse($model->validate());
    }
}
