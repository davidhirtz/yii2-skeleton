<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing;

enum RouteMode
{
    case Both;
    case ParseOnly;
    case CreateOnly;
}
