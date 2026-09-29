<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use DateTimeImmutable;
use Stringable;

/**
 * A `datetime` column's value, written as UTC. Immutable, so a record's attribute and its old value share one
 * instance: `modify()` answers a new date, which reads as a change, and never edits the one the record loaded.
 */
class DateTime extends DateTimeImmutable implements Stringable
{
    public function __toString(): string
    {
        return gmdate('Y-m-d H:i:s', $this->getTimestamp());
    }
}
