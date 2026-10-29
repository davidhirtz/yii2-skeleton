<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Widgets\Widget;
use Override;

/**
 * Repeats the guest check of {@see AsideMenu::isVisible()}, since the aside renders after the navbar (#190).
 */
abstract class AbstractAsideButton extends Widget
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && !$this->webuser->getIsGuest();
    }
}
