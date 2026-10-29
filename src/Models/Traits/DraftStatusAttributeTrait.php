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
    protected function getDefaultStatuses(): array
    {
        return [
            Status::make(static::STATUS_ENABLED)
                ->name(Yii::t('skeleton', 'STATUS_ENABLED_NAME'))
                ->icon('globe'),
            Status::make(static::STATUS_DRAFT)
                ->name(Yii::t('skeleton', 'STATUS_DRAFT_NAME'))
                ->icon('edit'),
            Status::make(static::STATUS_DISABLED)
                ->name(Yii::t('skeleton', 'STATUS_DISABLED_NAME'))
                ->icon('exclamation-triangle'),
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === static::STATUS_DRAFT;
    }
}
