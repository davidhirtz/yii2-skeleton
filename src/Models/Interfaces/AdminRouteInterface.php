<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

interface AdminRouteInterface
{
    /**
     * @return array<array-key, mixed>|false
     */
    public function getAdminRoute(): array|false;
}
