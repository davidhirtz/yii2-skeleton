<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Html\A;
use Override;

class DropdownOption extends A
{
    #[Override]
    protected function before(): string
    {
        $this->addClass('dropdown-option');
        return parent::before();
    }
}
