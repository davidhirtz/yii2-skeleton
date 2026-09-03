<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing;

interface RouteCompilerInterface
{
    /**
     * @return array<array-key, mixed> the router's native rule declarations
     */
    public function compile(Route ...$routes): array;
}
