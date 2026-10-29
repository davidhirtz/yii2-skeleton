<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

/**
 * @see \Hirtz\Skeleton\Models\Traits\StaleSaveTrait
 */
interface StaleSaveInterface extends TrailModelInterface
{
    public function getLoadedAt(): ?int;

    public function getLoadedVersion(): ?int;

    public function getStaleSaveVersion(): int;
}
