<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Buttons\AsideToggleButton;
use Hirtz\Skeleton\Search\Search;
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
            ->content($this->getSearchItem(), $this->getLanguageDropdownItem(), $this->getMobileToggle());
    }

    /**
     * The navbar sits outside `#wrap`, whose `hx-select` and `hx-target` the body declares for every element: the
     * input has to override both, or a keystroke swaps the whole page. Deliberately not a `<form>` — it would be
     * the first one on every admin page and a functional test's `$this->submit('form')` would find it.
     */
    protected function getSearchItem(): ?Stringable
    {
        if ($this->webuser->getIsGuest() || !Search::getComponent()->isEnabled()) {
            return null;
        }

        $results = Div::make()
            ->class('navbar-search-results')
            ->attribute('data-search-results', '')
            ->attribute('popover', 'manual');

        $input = TextInput::make()
            ->addClass('navbar-search-input')
            ->type('search')
            ->name('q')
            ->value(Yii::$app->getRequest()->get('q'))
            ->autocomplete('off')
            ->placeholder(Yii::t('skeleton', 'SEARCH_PLACEHOLDER'))
            ->addAttributes([
                'aria-label' => Yii::t('skeleton', 'SEARCH_LABEL'),
                'hx-get' => Url::toRoute(['/admin/search/suggest']),
                'hx-push-url' => 'false',
                'hx-select' => '#' . SearchController::LIST_ID,
                // Only the literal `unset` stops htmx from inheriting the body's out-of-band flash selector.
                'hx-select-oob' => 'unset',
                'hx-swap' => 'innerHTML',
                'hx-target' => '#' . $results->getId(),
                'hx-trigger' => 'input changed delay:250ms',
            ]);

        $button = Button::make()
            ->primary()
            ->addClass('navbar-search-toggle')
            ->type('button')
            ->attribute('aria-label', Yii::t('skeleton', 'SEARCH_LABEL'))
            ->attribute('data-search-toggle', '')
            ->icon('search');

        return Div::make()
            ->class('navbar-search')
            ->attribute('data-search', Url::toRoute(['/admin/search/index']))
            // The Enter key issues a boosted-style request from this element, which pushes the URL as a link would.
            ->attribute('hx-push-url', 'true')
            ->content($input, $button, $results);
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
        return AsideToggleButton::make();
    }
}
