<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;
use Hirtz\Skeleton\Models\Traits\DraftStatusAttributeTrait;
use Hirtz\Skeleton\Test\TestCase;
use Yii;
use yii\base\Model;

class DraftStatusAttributeTraitTest extends TestCase
{
    public function testDraftStatus(): void
    {
        $model = new class () extends Model implements DraftStatusAttributeInterface {
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
