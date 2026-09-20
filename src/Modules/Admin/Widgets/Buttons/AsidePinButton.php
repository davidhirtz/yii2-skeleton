<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * Collapses the aside to its icons from the `md` breakpoint up, where it is always in flow and costs a laptop
 * screen a good part of its width. Like {@see ColorSchemeDropdownButton} it re-renders nothing: the state is a
 * cookie plus an attribute on `<html>`, both written by `includes/aside.ts` and rendered by the layout, which
 * is what keeps a collapsed aside from unfolding for a frame on every full load. Both labels travel with the
 * button so the script can swap them without a round trip.
 */
class AsidePinButton extends Widget
{
    use ModuleTrait;

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && !$this->webuser->getIsGuest();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $module = static::getModule();
        $collapsed = $module->isAsideCollapsed();

        // One `Yii::t()` call per key, or `yii message` extracts neither and deletes both on the next run.
        $pin = Yii::t('skeleton', 'ASIDE_PIN');
        $unpin = Yii::t('skeleton', 'ASIDE_UNPIN');

        return Button::make()
            ->addClass('btn aside-pin')
            ->attribute('data-aside-pin', true)
            // The script must write the same `Secure` flag the server would, or an https page plants a twin no
            // plain-http response can overwrite.
            ->attribute('data-aside-pin-secure', $module->getAsideCookie()->secure)
            ->attribute('data-aside-pin-label', $unpin)
            ->attribute('data-aside-pin-label-collapsed', $pin)
            ->attribute('aria-pressed', $collapsed ? 'false' : 'true')
            // `includes/tooltips.ts` moves the `title` into an element of its own and removes the attribute, so
            // the button would otherwise be left without an accessible name.
            ->attribute('aria-label', $collapsed ? $pin : $unpin)
            ->type('button')
            ->icon($collapsed ? 'thumbtack-slash' : 'thumbtack')
            ->tooltip($collapsed ? $pin : $unpin);
    }
}
