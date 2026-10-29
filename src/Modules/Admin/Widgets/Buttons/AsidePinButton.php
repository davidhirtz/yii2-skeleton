<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Override;
use Stringable;
use Yii;

/**
 * Re-renders nothing: the layout renders the state `includes/aside.ts` writes, so a collapsed aside never unfolds for a
 * frame.
 */
class AsidePinButton extends AbstractAsideButton
{
    use ModuleTrait;

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $module = static::getModule();
        $collapsed = $module->isAsideCollapsed();

        // One `Yii::t()` call per key, or `yii message` extracts neither and deletes both on the next run.
        $pin = Yii::t('skeleton', 'ASIDE_PIN_BUTTON');
        $unpin = Yii::t('skeleton', 'ASIDE_UNPIN_BUTTON');

        return Button::make()
            ->addClass('btn btn-border aside-pin')
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
