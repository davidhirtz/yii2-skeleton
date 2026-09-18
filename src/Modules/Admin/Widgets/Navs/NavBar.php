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
        $items = $this->getItems();

        return $items
            ? Div::make()
                ->attributes($this->attributes)
                ->addClass('navbar')
                ->content($items)
            : '';
    }

    /**
     * Every item is guarded — the search and the toggle by the identity, the language dropdown by the number of
     * admin languages — so a logged out view is left with a bar containing nothing at all.
     */
    protected function getItems(): ?Stringable
    {
        $content = implode('', array_map(strval(...), array_filter([
            $this->getSearchItem(),
            $this->getLanguageDropdownItem(),
            $this->getMobileToggle(),
        ])));

        return $content
            ? Div::make()
                ->class('navbar-items')
                ->content($content)
            : null;
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
