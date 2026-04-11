<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Configure
{
    public function __construct(public string $method)
    {
    }
}

