<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\navs;

use Hirtz\Skeleton\html\A;

class DropdownLink extends A
{
    #[\Override]
    protected function before(): string
    {
        $this->addClass('dropdown-link');
        return parent::before();
    }
}
