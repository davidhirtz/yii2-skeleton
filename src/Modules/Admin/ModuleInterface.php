<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin;

use Hirtz\Skeleton\Widgets\Navs\Nav;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;

interface ModuleInterface
{
    public function aside(Nav $nav): Nav;

    public function dashboard(Dashboard $dashboard): Dashboard;
}
