<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class AsideToggleButton extends Widget
{
    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
            ->secondary()
            ->addClass('aside-toggle')
            ->addAttributes(['data-aside' => ''])
            ->icon('bars')
            ->attribute('aria-label', Lang::t('skeleton', 'NAV_BAR_TOGGLE_MENU'));
    }
}
