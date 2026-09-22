<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\NavItem;
use Override;
use Stringable;
use Yii;

/**
 * Collapses the aside to its icons from the `md` breakpoint up, where it is always in flow and costs a laptop
 * screen a good part of its width. It re-renders nothing: the state is a cookie plus an attribute on `<html>`,
 * both written by `includes/aside.ts` and rendered by the layout, which is what keeps a collapsed aside from
 * unfolding for a frame on every full load. Both labels travel with the button so the script can swap them
 * without a round trip.
 */
class AsidePinNavItem extends NavItem
{
    use ModuleTrait;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        // Above {@see SystemNavItem}'s 999, so the item stays last in a menu a bundle has added to.
        $this->order ??= 1000;
        $this->roles ??= [User::ROLE_AUTHENTICATED];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->addClass('aside-pin');

        if (!$this->content) {
            $this->content($this->getButton());
        }

        parent::configure();
    }

    protected function getButton(): Stringable
    {
        $module = static::getModule();
        $collapsed = $module->isAsideCollapsed();

        // One `Yii::t()` call per key, or `yii message` extracts neither and deletes both on the next run.
        $pin = Yii::t('skeleton', 'ASIDE_PIN');
        $unpin = Yii::t('skeleton', 'ASIDE_UNPIN');

        // The icon and the label are passed as content rather than through `icon()` and `text()`, which would
        // wrap them in an `.icon-text` of their own: the markup has to match every other nav link, or the aside
        // has no label to hide while it is collapsed to its icons.
        return Button::make()
            ->content(
                Icon::make()
                    ->name($collapsed ? 'angle-double-right' : 'angle-double-left')
                    ->addClass('nav-link-icon'),
                Span::make()
                    ->class('nav-link-label')
                    ->text($collapsed ? $pin : $unpin),
            )
            ->class('nav-link')
            ->attribute('data-aside-pin', true)
            // The script must write the same `Secure` flag the server would, or an https page plants a twin no
            // plain-http response can overwrite.
            ->attribute('data-aside-pin-secure', $module->getAsideCookie()->secure)
            ->attribute('data-aside-pin-label', $unpin)
            ->attribute('data-aside-pin-label-collapsed', $pin)
            ->attribute('aria-pressed', $collapsed ? 'false' : 'true')
            ->type('button');
    }
}
