<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Navs\DropdownOptionLink;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

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

        $icon = Icon::make()
            ->collection(Icon::ICON_COLLECTION_FLAG)
            ->name(Yii::$app->language);

        $button = Button::make()
            ->primary()
            ->content($icon);

        $dropdown = Dropdown::make()
            ->button($button)
            ->popover(fn (Div $tag) => $tag->attribute('id', 'i18n'));

        foreach ($languages as $language) {
            $label = $i18n->getLabel($language);

            $link = DropdownOptionLink::make()
                ->addClass('i18n-dropdown-option')
                ->content(
                    Icon::make()
                        ->collection(Icon::ICON_COLLECTION_FLAG)
                        ->name($language),
                    Div::make()->addText($label)
                );

            // The navbar sits outside `#wrap`, so a boosted swap would leave the flag and the labels in the
            // previous language.
            $link->href($this->getUrl($language))
                ->attribute('hx-boost', 'false');

            $dropdown->addItem($link);
        }

        return $dropdown;
    }

    /**
     * With `UrlManager::$i18nUrl` the language parameter is turned into a path prefix, which is the language of the
     * frontend URL and not the one the admin runs in — so the parameter is appended to the plain current URL here.
     */
    protected function getUrl(string $language): string
    {
        $param = Yii::$app->getRequest()->languageParam;
        $url = Url::current([$param => null]);

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query([$param => $language]);
    }
}
