<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\models\interfaces\DraftStatusAttributeInterface;
use davidhirtz\yii2\skeleton\models\traits\DraftStatusAttributeTrait;
use Yii;
use yii\base\Model;

class DraftStatusAttributeTraitTest extends Unit
{
    public function testDraftStatus()
    {
        $model = new class() extends Model implements DraftStatusAttributeInterface {
            use DraftStatusAttributeTrait;

            public ?int $status = self::STATUS_DEFAULT;
        };

        $model->status = $model::STATUS_DRAFT;
        self::assertTrue($model->isDraft());

        self::assertEquals(Yii::t('skeleton', 'Draft'), $model->getStatusName());
        self::assertEquals('edit', $model->getStatusIcon());

        $model->status = $model::STATUS_DISABLED;
        self::assertTrue($model->isDisabled());
    }
}
