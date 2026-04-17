<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Navs\DropdownOptionLink;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class NavBar extends Widget
{
    use TagAttributesTrait;

    protected ?array $languageRoute = null;

    #[Override]
    protected function renderContent(): Stringable|string
    {
        return Div::make()
            ->attributes($this->attributes)
            ->addClass('navbar')
            ->content($this->getLanguageDropdownItem(), $this->getMobileToggle());
    }

    protected function getLanguageDropdownItem(): ?Stringable
    {
        $i18n = Yii::$app->getI18n();

        if (count($i18n->getLanguages()) < 2) {
            return null;
        }

        $icon = Icon::make()
            ->collection(Icon::ICON_COLLECTION_FLAG)
            ->name(Yii::$app->language);

        $button = Button::make()
            ->primary()
            ->content($icon);

        $dropdown = Dropdown::make()
            ->button($button)
            ->dropend()
            ->popover(fn (Div $tag) => $tag->attribute('id', 'i18n'));

        foreach ($i18n->getLanguages() as $language) {
            $label = $i18n->getLabel($language);

            $link = DropdownOptionLink::make()
                ->addClass('i18n-dropdown-option')
                ->content(
                    Icon::make()
                        ->collection(Icon::ICON_COLLECTION_FLAG)
                        ->name($language),
                    Div::make()->addText($label)
                );

            if ($this->languageRoute) {
                $link->href([
                    ...Yii::$app->getRequest()->getQueryParams(),
                    ...$this->languageRoute,
                    'language' => $language,
                ]);
            } else {
                $link->current(['language' => $language]);
            }

            $dropdown->addItem($link);
        }

        return $dropdown;
    }

    protected function getMobileToggle(): ?Stringable
    {
        return Button::make()
            ->secondary()
            ->addClass('aside-toggle')
            ->icon('bars')
            ->attribute('onclick', "body.classList.toggle('has-aside')")
            ->attribute('aria-label', Yii::t('skeleton', 'Toggle menu'));
    }
}
