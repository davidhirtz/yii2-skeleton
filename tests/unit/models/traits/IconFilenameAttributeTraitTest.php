<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use Hirtz\Skeleton\base\traits\ModelTrait;
use Hirtz\Skeleton\helpers\FileHelper;
use Hirtz\Skeleton\models\traits\IconFilenameAttributeTrait;
use Hirtz\Skeleton\validators\DynamicRangeValidator;
use Yii;
use yii\base\Model;

class IconFilenameAttributeTraitTest extends Unit
{
    public function testTraitAttributeLabels(): void
    {
        $model = new class () extends Model {
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

        $label = $model->getAttributeLabel($model->iconFilenameAttribute);
        $this::assertEquals($label, Yii::t('skeleton', 'Icon'));
    }

    public function testTraitRules(): void
    {
        $model = new class () extends Model {
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

        static::assertEquals(2, count($model->getActiveValidators()));
        static::assertEquals(DynamicRangeValidator::class, $model->getActiveValidators($model->iconFilenameAttribute)[0]::class);
    }

    public function testIconFilenames(): void
    {
        Yii::setAlias('@webroot', '@runtime');
        FileHelper::createDirectory('@runtime/images/icons');

        $alias = Yii::getAlias('@runtime/images/icons');

        file_put_contents("$alias/test-image.svg", '');
        file_put_contents("$alias/not-an-image.txt", '');

        $model = new class () extends Model {
            use IconFilenameAttributeTrait;

            public ?string $icon_filename = null;
        };

        self::assertEquals(['test-image.svg' => 'Test Image'], $model->getIconFilenames());
        self::assertEquals('', $model->getIcon());

        $model->icon_filename = 'test-image.svg';
        self::assertEquals('/images/icons/test-image.svg', $model->getIcon());

        FileHelper::removeDirectory('@runtime/images');
    }
}
