<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use DateTimeImmutable;
use Override;
use Stringable;

/**
 * Written as UTC, and immutable so a record's attribute and its old value can share one instance.
 */
class DateTime extends DateTimeImmutable implements Stringable
{
    #[Override]
    public function __toString(): string
    {
        return gmdate('Y-m-d H:i:s', $this->getTimestamp());
    }
}
