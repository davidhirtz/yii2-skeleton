<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\models\interfaces\DraftStatusAttributeInterface;
use davidhirtz\yii2\skeleton\models\traits\DraftStatusAttributeTrait;
use Yii;
use yii\base\Model;

class DraftStatusAttributeModelTest extends Unit
{
    public function testDraftStatus()
    {
        $model = new class() extends Model implements DraftStatusAttributeInterface {
            use DraftStatusAttributeTrait;

            public ?int $status = self::STATUS_DEFAULT;
        };

        $model->status = $model::STATUS_DRAFT;
        $this->assertTrue($model->isDraft());

        $this->assertEquals(Yii::t('skeleton', 'Draft'), $model->getStatusName());
        $this->assertEquals('edit', $model->getStatusIcon());

        $model->status = $model::STATUS_DISABLED;
        $this->assertTrue($model->isDisabled());
    }
}
