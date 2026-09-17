<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\TextInput;
use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Search\Search;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * The navbar sits outside `#wrap`, whose `hx-select` and `hx-target` the body declares for every element: the input
 * has to override both, or a keystroke swaps the whole page. Deliberately not a `<form>` — it would be the first one
 * on every admin page and a functional test's `$this->submit('form')` would find it.
 */
class NavBarSearch extends Widget
{
    final public const string RESULTS_ID = 'search-results';

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible()
            && !$this->webuser->getIsGuest()
            && Search::getComponent()->isEnabled();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Div::make()
            ->class('navbar-search')
            ->attribute('data-search', Url::toRoute(['/admin/search/index']))
            // The Enter key issues a boosted-style request from this element, which pushes the URL as a link would.
            ->attribute('hx-push-url', 'true')
            ->content($this->getInput(), $this->getToggle(), $this->getResults());
    }

    protected function getInput(): Stringable
    {
        return TextInput::make()
            ->addClass('navbar-search-input')
            ->type('search')
            ->name('q')
            ->value(Application::current()->getRequest()->get('q'))
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
                'hx-target' => '#' . self::RESULTS_ID,
                'hx-trigger' => 'input changed delay:250ms',
            ]);
    }

    /**
     * Both icons are rendered and the open state picks one in CSS, so the toggle needs no second render. The
     * disclosure state is `aria-expanded` rather than a second label, which the script keeps in sync.
     */
    protected function getToggle(): Stringable
    {
        return Button::make()
            ->transparent()
            ->addClass('navbar-search-toggle')
            ->type('button')
            ->attribute('aria-label', Yii::t('skeleton', 'SEARCH_LABEL'))
            ->attribute('aria-expanded', 'false')
            ->attribute('data-search-toggle', '')
            ->content(
                Icon::make()->name('search')->addClass('navbar-search-toggle-open'),
                Icon::make()->name('xmark')->addClass('navbar-search-toggle-close'),
            );
    }

    protected function getResults(): Stringable
    {
        return Div::make()
            ->attribute('id', self::RESULTS_ID)
            ->class('navbar-search-results')
            ->attribute('data-search-results', '')
            ->attribute('popover', 'manual');
    }
}
