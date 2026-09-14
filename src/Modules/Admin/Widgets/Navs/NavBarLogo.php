<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\Traits\LogoTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class NavBarLogo extends Widget
{
    use LogoTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Div::make()
            ->class('navbar-logo')
            ->content($this->getLink());
    }
}
