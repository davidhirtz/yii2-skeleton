<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

/**
 * @see AccountController::actionLanguage()
 */
class LanguageDropdownButton extends Widget
{
    use ModuleTrait;

    protected function renderContent(): string|Stringable
    {
        $languages = static::getModule()->getLanguages();

        if (count($languages) < 2) {
            return '';
        }

        $i18n = Yii::$app->getI18n();
        $current = Yii::$app->language;

        $button = Button::make()
            ->class('btn navbar-btn')
            ->content(strtoupper($i18n->getLanguageCode($current)))
            ->attribute('aria-label', $i18n->getLabel($current));

        $dropdown = Dropdown::make()
            ->button($button)
            // The navbar renders outside `#wrap`, so nothing here inherits its CSRF header.
            ->attribute('hx-headers:inherited', $this->getCsrfHeaders())
            ->popover(fn (Div $tag) => $tag->attribute('id', 'i18n'));

        foreach ($languages as $language) {
            $dropdown->addItem($this->getLanguageButton($language, $i18n->getLabel($language), $current));
        }

        return $dropdown;
    }

    protected function getLanguageButton(string $language, string $label, ?string $current): Stringable
    {
        return Button::make()
            ->addClass('dropdown-option', $language === $current ? 'selected' : null)
            ->type('button')
            ->text($label)
            ->post(['/admin/account/language'])
            ->attribute('hx-vals', (string)json_encode(['language' => $language]));
    }

    protected function getCsrfHeaders(): string
    {
        $token = Application::current()->getRequest()->getCsrfToken();
        return (string)json_encode(['X-CSRF-TOKEN' => $token]);
    }
}
