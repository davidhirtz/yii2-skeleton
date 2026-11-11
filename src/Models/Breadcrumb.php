<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

final readonly class Breadcrumb
{
    /**
     * A link out of the admin, like the frontend, has no `#wrap` to swap: `$boost` is `false`.
     *
     * @param array<int|string, mixed>|string|null $url
     */
    public function __construct(
        public string $label,
        public array|string|null $url = null,
        public bool $boost = true,
    ) {
    }
}
