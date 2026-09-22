<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Widgets\Widget;
use Override;

/**
 * A navbar button standing in for the aside, which renders after it and cannot be asked. Both answer the guest
 * check {@see AsideMenu::isVisible()} comes to for everything shipped — a bar carrying a toggle for an aside
 * that had left itself out of the document is monorepo issue #190, and the pin beside it is the same trap.
 */
abstract class AbstractAsideButton extends Widget
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && !$this->webuser->getIsGuest();
    }
}
