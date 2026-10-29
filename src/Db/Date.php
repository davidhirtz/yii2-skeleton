<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use DateTimeImmutable;
use Stringable;

/**
 * Never shifted to UTC, unlike {@see DateTime}, which would move the day.
 */
class Date extends DateTimeImmutable implements Stringable
{
    public function __toString(): string
    {
        return $this->format('Y-m-d');
    }
}
