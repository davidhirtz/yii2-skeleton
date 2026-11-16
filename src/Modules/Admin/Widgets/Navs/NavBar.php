<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsidePinButton;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsideToggleButton;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\ContentLanguageDropdownButton;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\LanguageDropdownButton;
use Hirtz\Skeleton\Web\User as WebUser;
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

    protected function getItems(): ?Stringable
    {
        $content = implode('', array_map(strval(...), array_filter([
            $this->getSearchItem(),
            $this->getLanguageDropdownItem(),
            $this->getAsidePinItem(),
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

    /**
     * Logged in, the admin's language is the account's and the dropdown picks the content language.
     */
    protected function getLanguageDropdownItem(): ?Stringable
    {
        return WebUser::current()?->getIsGuest() === false
            ? ContentLanguageDropdownButton::make()
            : LanguageDropdownButton::make();
    }

    protected function getAsidePinItem(): ?Stringable
    {
        return AsidePinButton::make();
    }

    protected function getMobileToggle(): ?Stringable
    {
        return AsideToggleButton::make();
    }
}
