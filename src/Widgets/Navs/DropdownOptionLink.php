<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Link;
use Override;
use Stringable;

class DropdownOptionLink extends Link
{
    #[Override]
    protected function configure(): void
    {
        $this->addClass('dropdown-option');
        parent::configure();
    }
}
