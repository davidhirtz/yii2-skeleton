<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Closure;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Statuses\Status;
use Yii;

/**
 * @property int $status
 */
trait StatusAttributeTrait
{
    /**
     * @var Closure(): list<Status>|list<Status>|null
     */
    private Closure|array|null $configuredStatuses = null;

    /**
     * A closure, for the same reason as {@see TypeAttributeTrait::setTypes()}.
     *
     * @param Closure(): list<Status>|list<Status> $statuses
     */
    public function setStatuses(Closure|array $statuses): void
    {
        $this->configuredStatuses = $statuses;
    }

    /**
     * @return list<Status>
     */
    public function getStatuses(): array
    {
        if ($this->configuredStatuses !== null) {
            return $this->configuredStatuses instanceof Closure
                ? ($this->configuredStatuses)()
                : $this->configuredStatuses;
        }

        return $this->getDefaultStatuses();
    }

    /**
     * Unlike an override of {@see static::getStatuses()}, an override of this still gives way to what the container
     * configured.
     *
     * @return list<Status>
     */
    protected function getDefaultStatuses(): array
    {
        return [
            Status::make(static::STATUS_ENABLED)
                ->name(Yii::t('skeleton', 'STATUS_ENABLED_NAME'))
                ->icon('globe'),
            Status::make(static::STATUS_DISABLED)
                ->name(Yii::t('skeleton', 'STATUS_DISABLED_NAME'))
                ->icon('exclamation-triangle'),
        ];
    }

    /**
     * @return array<int, Status>
     */
    public static function getStatusDefinitions(): array
    {
        return DefinitionRegistry::get(static::class, 'getStatuses', Status::class);
    }

    public static function findStatus(?int $status): ?Status
    {
        return $status === null ? null : (static::getStatusDefinitions()[$status] ?? null);
    }

    public function getStatus(): ?Status
    {
        return static::findStatus($this->status ?? null);
    }

    /**
     * `null` with fewer than two statuses, or for a stored status the configuration dropped, which is left alone
     * rather than moved to the first.
     */
    public function getNextStatus(): ?Status
    {
        $definitions = static::getStatusDefinitions();
        $values = array_keys($definitions);
        $index = array_search($this->getStatus()?->value, $values, true);

        return $index === false || count($values) < 2
            ? null
            : $definitions[$values[($index + 1) % count($values)]];
    }

    /**
     * {@see \Hirtz\Skeleton\Models\User} refuses for the site owner, whose star is not one of the statuses at all.
     */
    public function isStatusUpdatable(): bool
    {
        return true;
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
