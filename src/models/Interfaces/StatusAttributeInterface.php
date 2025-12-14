<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

interface StatusAttributeInterface
{
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    public const STATUS_DEFAULT = self::STATUS_ENABLED;

    public static function getStatuses(): array;

    public function getStatusName(): string;

    public function getStatusIcon(): string;

    public function isEnabled(): bool;

    public function isDisabled(): bool;
}
