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
     * @var Closure(): list<Status>|list<Status>|null what the container configured, see {@see static::setStatuses()}
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
     * Override this method to implement statuses — an override owns them, and the configured list is then ignored.
     *
     * @return list<Status>
     */
    public function getStatuses(): array
    {
        if ($this->configuredStatuses !== null) {
            return $this->configuredStatuses instanceof Closure
                ? ($this->configuredStatuses)()
                : $this->configuredStatuses;
        }

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
     * The next declared status, wrapping at the end of the list. `null` where the model declares fewer than two, or
     * where the record's own value is not among them — a record stored with a status the configuration has since
     * dropped is left alone rather than silently moved to the first one.
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
