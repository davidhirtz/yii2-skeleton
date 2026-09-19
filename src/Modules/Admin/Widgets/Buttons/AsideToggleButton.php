<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AsideMenu;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class AsideToggleButton extends Widget
{
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && !$this->webuser->getIsGuest();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
            ->addClass('btn aside-toggle')
            ->addAttributes(['data-aside' => ''])
            ->icon('bars')
            ->attribute('aria-label', Yii::t('skeleton', 'NAV_BAR_TOGGLE_MENU'));
    }
}
