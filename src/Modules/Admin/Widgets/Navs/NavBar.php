<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsideToggleButton;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\LanguageDropdownButton;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class NavBar extends Widget
{
    use TagAttributesTrait;

    #[Override]
    protected function renderContent(): Stringable|string
    {
        return Div::make()
            ->attributes($this->attributes)
            ->addClass('navbar')
            ->content($this->getSearchItem(), $this->getLanguageDropdownItem(), $this->getMobileToggle());
    }

    protected function getSearchItem(): ?Stringable
    {
        return NavBarSearch::make();
    }

    protected function getLanguageDropdownItem(): ?Stringable
    {
        return LanguageDropdownButton::make();
    }

    protected function getMobileToggle(): ?Stringable
    {
        return AsideToggleButton::make();
    }
}
