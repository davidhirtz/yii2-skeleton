<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;

class DropdownLink extends A
{
    #[\Override]
    protected function before(): string
    {
        $this->addClass('dropdown-link');
        return parent::before();
    }
}
