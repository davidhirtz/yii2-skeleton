<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\I18n\Lang;
use Yii;

/**
 * @property int $status
 */
trait StatusAttributeTrait
{
    public static function getStatuses(): array
    {
        return [
            static::STATUS_ENABLED => [
                'name' => Lang::t('skeleton', 'COMMON_ENABLED'),
                'icon' => 'globe',
            ],
            static::STATUS_DISABLED => [
                'name' => Lang::t('skeleton', 'COMMON_DISABLED'),
                'icon' => 'exclamation-triangle',
            ],
        ];
    }

    public function getStatusName(): string
    {
        return static::getStatuses()[$this->status]['name'] ?? '';
    }

    public function getStatusIcon(): string
    {
        return static::getStatuses()[$this->status]['icon'] ?? '';
    }

    public function isEnabled(): bool
    {
        return $this->status >= static::STATUS_ENABLED;
    }

    public function isDisabled(): bool
    {
        return $this->status === static::STATUS_DISABLED;
    }
}
