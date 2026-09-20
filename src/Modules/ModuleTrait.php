<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules;

use Hirtz\Skeleton\Modules\Admin\Module;

trait ModuleTrait
{
    public static function getModule(): Module
    {
        return Module::current();
    }
}
