<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Override;
use Stringable;
use Yii;

/**
 * Below the `md` breakpoint only, where {@see AsidePinButton} takes the same slot over.
 */
class AsideToggleButton extends AbstractAsideButton
{
    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
            ->addClass('btn btn-border aside-toggle')
            ->addAttributes(['data-aside' => ''])
            ->icon('bars')
            ->attribute('aria-label', Yii::t('skeleton', 'ASIDE_TOGGLE_LABEL'));
    }
}
