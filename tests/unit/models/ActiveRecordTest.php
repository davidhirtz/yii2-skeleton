<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\models\traits\IconFilenameAttributeTrait;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

class ActiveRecordTest extends Unit
{
    public function testTraitAttributeLabels()
    {
        $class = new class extends ActiveRecord {
            use IconFilenameAttributeTrait;

            public function attributeLabels(): array
            {
                return [
                    ...$this->getTraitAttributeLabels(),
                    'other' => 'Other',
                ];
            }
        };

        $label = $class->getAttributeLabel($class->iconFilenameAttribute);
        static::assertEquals($label, Yii::t('skeleton', 'Icon'));
    }

    public function testBatchInsert(): void
    {
        $data = require __DIR__ . '/../../_data/user.php';
        $expected = count($data);

        $count = User::batchInsert($data);
        static::assertEquals($expected, $count);

        $count = User::find()->count();
        static::assertEquals($expected, $count);
    }
}