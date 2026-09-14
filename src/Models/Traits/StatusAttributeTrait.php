<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Statuses\Status;
use Yii;

/**
 * @property int $status
 */
trait StatusAttributeTrait
{
    /**
     * @return list<Status>
     */
    public static function getStatuses(): array
    {
        return [
            Status::make(static::STATUS_ENABLED)
                ->name(Yii::t('skeleton', 'COMMON_ENABLED'))
                ->icon('globe'),
            Status::make(static::STATUS_DISABLED)
                ->name(Yii::t('skeleton', 'COMMON_DISABLED'))
                ->icon('exclamation-triangle'),
        ];
    }

    /**
     * @return array<int|string, Status>
     */
    public static function getStatusDefinitions(): array
    {
        return DefinitionRegistry::get(static::class, 'getStatuses', Status::class);
    }

    public static function findStatus(int|string|null $status): ?Status
    {
        return $status === null || $status === '' ? null : (static::getStatusDefinitions()[$status] ?? null);
    }

    public function getStatus(): ?Status
    {
        return static::findStatus($this->status ?? null);
    }

    public function getStatusName(): string
    {
        return $this->getStatus()?->getName() ?? '';
    }

    public function getStatusIcon(): string
    {
        return $this->getStatus()?->getIcon() ?? '';
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
