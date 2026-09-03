<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\I18n\Lang;
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
                'name' => Lang::t('skeleton', 'COMMON_ENABLED'),
                'icon' => 'globe',
            ],
            static::STATUS_DRAFT => [
                'name' => Lang::t('skeleton', 'DRAFT_STATUS_ATTRIBUTE_DRAFT'),
                'icon' => 'edit',
            ],
            static::STATUS_DISABLED => [
                'name' => Lang::t('skeleton', 'COMMON_DISABLED'),
                'icon' => 'exclamation-triangle',
            ],
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === static::STATUS_DRAFT;
    }
}
