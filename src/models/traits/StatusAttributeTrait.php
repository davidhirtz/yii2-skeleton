<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\traits;

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
                'name' => Yii::t('skeleton', 'Enabled'),
                'icon' => 'globe',
            ],
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'Disabled'),
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
