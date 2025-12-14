<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Interfaces\DraftStatusAttributeInterface;
use Yii;

/**
 * @mixin DraftStatusAttributeInterface
 */
trait DraftStatusAttributeTrait
{
    use StatusAttributeTrait;

    public static function getStatuses(): array
    {
        return [
            static::STATUS_ENABLED => [
                'name' => Yii::t('skeleton', 'Enabled'),
                'icon' => 'globe',
            ],
            static::STATUS_DRAFT => [
                'name' => Yii::t('skeleton', 'Draft'),
                'icon' => 'edit',
            ],
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'Disabled'),
                'icon' => 'exclamation-triangle',
            ],
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === static::STATUS_DRAFT;
    }
}
