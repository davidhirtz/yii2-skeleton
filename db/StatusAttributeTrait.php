<?php

namespace davidhirtz\yii2\skeleton\db;

use JetBrains\PhpStorm\Deprecated;
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

    public function isDraft(): bool
    {
        return $this->status == static::STATUS_DRAFT;
    }

    public function isDisabled(): bool
    {
        return $this->status == static::STATUS_DISABLED;
    }
}