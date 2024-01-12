<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\traits\IconFilenameAttributeTrait;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use Yii;
use yii\base\Model;

class ModelTest extends Unit
{
    public function testTraitAttributeLabels(): void
    {
        $record = new class() extends Model {
            use IconFilenameAttributeTrait;
            use ModelTrait;

            public function attributeLabels(): array
            {
                return [
                    ...$this->getTraitAttributeLabels(),
                    'other' => 'Other',
                ];
            }
        };

        $label = $record->getAttributeLabel($record->iconFilenameAttribute);
        $this::assertEquals($label, Yii::t('skeleton', 'Icon'));
    }

    public function testTraitRules(): void
    {
        $record = new class() extends Model {
            use IconFilenameAttributeTrait;
            use ModelTrait;

            public string $other = '';

            public function rules(): array
            {
                return [
                    ...$this->getTraitRules(),
                    [
                        ['other'],
                        'string'
                    ],
                ];
            }
        };

        static::assertEquals(2, count($record->getActiveValidators()));
        static::assertEquals(DynamicRangeValidator::class, $record->getActiveValidators($record->iconFilenameAttribute)[0]::class);
    }
}
