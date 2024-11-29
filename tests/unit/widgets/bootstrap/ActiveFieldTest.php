<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\widgets\bootstrap;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveField;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use yii\base\Model;

class ActiveFieldTest extends Unit
{
    public function testHexColorField(): void
    {
        $model = new class() extends Model {
            public ?string $color = null;

            public function formName(): string
            {
                return 'Test';
            }
        };

        $field = new ActiveField([
            'form' => $this->createMock(ActiveForm::class),
            'model' => $model,
            'attribute' => 'color',
        ]);

        $expected = '<input type="color" id="test-color" class="form-control" name="Test[color]">';

        self::assertStringContainsString($expected, $field->hexColor()->render());

        $model->color = 'ccc';
        $expected = '<input type="color" id="test-color" class="form-control" name="Test[color]" value="#ccc">';

        self::assertStringContainsString($expected, $field->hexColor()->render());

        $expected = '<input type="color" id="test-color" class="form-control" name="Test[color]" value="#000">';

        self::assertStringContainsString($expected, $field->hexColor(['value' => '#000'])->render());
    }
}
