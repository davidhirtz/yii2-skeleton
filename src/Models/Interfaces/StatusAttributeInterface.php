<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Statuses\Status;

/**
 * @property int $status
 */
interface StatusAttributeInterface
{
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    public const STATUS_DEFAULT = self::STATUS_ENABLED;

    /**
     * The declaration, and the only instance method of the three: it is what an installation replaces through the
     * container, and {@see \Hirtz\Skeleton\Models\Definitions\DefinitionRegistry} is its only caller.
     *
     * @return list<Status>
     */
    public function getStatuses(): array;

    /**
     * @return array<int, Status>
     */
    public static function getStatusDefinitions(): array;

    public static function findStatus(?int $status): ?Status;

    public function getStatus(): ?Status;

    public function getStatusName(): string;

    public function getStatusIcon(): string;

    public function isEnabled(): bool;

    public function isDisabled(): bool;
}
