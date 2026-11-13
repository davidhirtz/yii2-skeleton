<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use DateTimeImmutable;
use Override;
use Stringable;

/**
 * Never shifted to UTC, unlike {@see DateTime}, which would move the day.
 */
class Date extends DateTimeImmutable implements Stringable
{
    #[Override]
    public function __toString(): string
    {
        return $this->format('Y-m-d');
    }
}
