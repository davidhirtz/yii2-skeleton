<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use DateTimeImmutable;
use Stringable;

/**
 * A `date` column's value: a calendar day at midnight in the application's time zone, written as it reads. Unlike
 * {@see DateTime} it is never shifted to UTC, which would move the day.
 */
class Date extends DateTimeImmutable implements Stringable
{
    public function __toString(): string
    {
        return $this->format('Y-m-d');
    }
}
