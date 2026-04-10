<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Link;
use Hirtz\Skeleton\Widgets\Traits\VisibilityTrait;
use Override;
use Stringable;

class DropdownOptionLink extends Link
{
    use VisibilityTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addClass('dropdown-option');
        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->isVisible() ? parent::renderContent() : '';
    }
}
