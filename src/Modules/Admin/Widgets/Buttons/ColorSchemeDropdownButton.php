<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\helpers\Url;

/**
 * Unlike {@see LanguageDropdownButton} the options carry no `hx-*`: switching the scheme re-renders nothing, so
 * `includes/colorScheme.ts` writes the cookie and the attribute on the spot. The route is only rendered for an
 * account, which is also how the script knows whether there is a column to persist to.
 *
 * @see AccountController::actionColorScheme()
 */
class ColorSchemeDropdownButton extends Widget
{
    use ModuleTrait;

    private const string POPOVER_ID = 'color-scheme';

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $module = static::getModule();
        $current = $module->getColorScheme();

        $button = Button::make()
            ->class('btn')
            ->attribute('data-color-scheme-icon', true)
            ->content(Icon::make()->name($this->getIconName($current)));

        $dropdown = Dropdown::make()
            ->attribute('data-color-scheme', true)
            // The script must write the same `Secure` flag the server would, or an https page plants a twin no
            // plain-http response can overwrite — which is the whole reason the flag is pinned rather than derived.
            ->attribute('data-color-scheme-secure', $module->getColorSchemeCookie()->secure)
            ->button($button)
            // Every id outside `#wrap` is a literal: the navbar survives a swap, so a generated one would be
            // reached by a later page's content.
            ->popover(fn (Div $tag) => $tag->attribute('id', self::POPOVER_ID));

        if (WebUser::current()?->getIsGuest() === false) {
            $dropdown->attribute('data-color-scheme-url', Url::to(['/admin/account/color-scheme']));
        }

        $dropdown->addItem($this->getColorSchemeButton('', Yii::t('skeleton', 'USER_COLOR_SCHEME_AUTO'), $current));

        foreach (User::getColorSchemes() as $scheme => $label) {
            $dropdown->addItem($this->getColorSchemeButton($scheme, $label, $current));
        }

        return $dropdown;
    }

    protected function getColorSchemeButton(string $scheme, string $label, ?string $current): Stringable
    {
        return Button::make()
            ->addClass($scheme === (string)$current ? 'dropdown-option selected' : 'dropdown-option')
            ->type('button')
            ->content(
                Icon::make()->name($this->getIconName($scheme ?: null)),
                Div::make()->addText($label)
            )
            ->attribute('data-color-scheme-value', $scheme);
    }

    protected function getIconName(?string $scheme): string
    {
        return match ($scheme) {
            User::COLOR_SCHEME_LIGHT => 'sun',
            User::COLOR_SCHEME_DARK => 'moon',
            default => 'circle-half-stroke',
        };
    }
}
