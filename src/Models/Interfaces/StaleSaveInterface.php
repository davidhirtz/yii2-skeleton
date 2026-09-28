<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

/**
 * A record whose form refuses a save that would overwrite a change made after the form was opened.
 *
 * @see \Hirtz\Skeleton\Models\Traits\StaleSaveTrait
 */
interface StaleSaveInterface extends TrailModelInterface
{
    public function getLoadedAt(): ?int;
}
