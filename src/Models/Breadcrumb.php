<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

final readonly class Breadcrumb
{
    public function __construct(
        public string $label,
        public array|string|null $url = null,
    )
    {
    }
}