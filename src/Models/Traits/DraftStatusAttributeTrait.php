<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;
use Hirtz\Skeleton\Models\Statuses\Status;
use Yii;

/**
 * @mixin DraftStatusAttributeInterface
 */
trait DraftStatusAttributeTrait
{
    use StatusAttributeTrait;

    /**
     * @return list<Status>
     */
    public function getStatuses(): array
    {
        return [
            Status::make(static::STATUS_ENABLED)
                ->name(Yii::t('skeleton', 'COMMON_ENABLED'))
                ->icon('globe'),
            Status::make(static::STATUS_DRAFT)
                ->name(Yii::t('skeleton', 'COMMON_STATUS_ATTRIBUTE_DRAFT'))
                ->icon('edit'),
            Status::make(static::STATUS_DISABLED)
                ->name(Yii::t('skeleton', 'COMMON_DISABLED'))
                ->icon('exclamation-triangle'),
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === static::STATUS_DRAFT;
    }
}
