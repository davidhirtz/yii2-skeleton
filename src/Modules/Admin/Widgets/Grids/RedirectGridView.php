<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\CheckboxColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeIconColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridFooter;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Hirtz\Skeleton\Widgets\Link;
use Hirtz\Skeleton\Widgets\Modal;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider|null $provider
 */
class RedirectGridView extends GridView
{
    public bool $showSelection = true;
    protected ?Redirect $redirect = null;

    public function redirect(Redirect $redirect): static
    {
        $this->redirect = $redirect;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'redirects';

        if ($this->redirect) {
            $this->setDataProviderFromRedirect();
            $this->setRedirectOptions();
        }

        $this->header ??= [
            $this->getTypeDropdown(),
            $this->getSearchInput(),
        ];

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getTypeColumn(),
            $this->getRequestUriColumn(),
            $this->getUrlColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        if ($this->showSelection) {
            $this->footer ??= GridFooter::make()
                ->attributes($this->footerAttributes)
                ->addClass('hidden block-has-checked')
                ->content($this->getSelectionButton());
        }

        parent::configure();
    }

    protected function setDataProviderFromRedirect(): void
    {
        $this->provider ??= Yii::createObject(RedirectActiveDataProvider::class);

        $this->provider->query
            ->andWhere(['url' => $this->redirect->getOldAttribute('url')])
            ->andWhere(['!=', 'id', $this->redirect->id]);
    }

    protected function setRedirectOptions(): void
    {
        $this->showSelection = false;
        $this->showOnEmpty = false;
        $this->layout = '{items}';
    }

    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->model(Redirect::instance());
    }

    protected function getCheckboxColumn(): ?CheckboxColumn
    {
        return $this->showSelection
            ? CheckboxColumn::make()
            : null;
    }

    protected function getTypeColumn(): ?Column
    {
        return TypeIconColumn::make();
    }

    protected function getRequestUriColumn(): ?Column
    {
        return DataColumn::make()
            ->property('request_uri')
            ->content(fn (Redirect $redirect): Stringable => A::make()
                ->content($this->search->markKeywords($redirect->request_uri))
                ->href($redirect->getAdminRoute()));
    }

    protected function getUrlColumn(): ?Column
    {
        return DataColumn::make()
            ->property('url')
            ->content(fn (Redirect $redirect) => Link::make()
                ->icon('external-link-alt')
                ->content($this->search->markKeywords($redirect->url ?: '/'))
                ->href($redirect->url)
                ->target('_blank'));
    }

    protected function getUpdatedAtColumn(): ?Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at')
            ->hiddenForSmallDevices();
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @see RedirectController::actionDelete()
     * @see RedirectController::actionUpdate()
     */
    protected function getButtonColumnContent(Redirect $redirect): array
    {
        return [
            ViewGridButton::make()
                ->model($redirect),
            DeleteGridButton::make()
                ->model($redirect),
        ];
    }

    /**
     * @see RedirectController::actionDeleteAll()
     */
    protected function getSelectionButton(): Stringable
    {
        $modal = Modal::make()
            ->title(Lang::t('skeleton', 'REDIRECT_DELETE_SELECTED'))
            ->text(Lang::t('skeleton', 'COMMON_CONFIRM_DELETE_SELECTED'))
            ->footer(Button::make()
                ->danger()
                ->text(Lang::t('skeleton', 'REDIRECT_DELETE_SELECTED'))
                ->icon('trash')
                ->post(['/admin/redirect/delete-all'])
                ->attribute('hx-include', '[data-check]:checked'));

        $button = Button::make()
            ->danger()
            ->text(Lang::t('skeleton', 'REDIRECT_DELETE_SELECTED'))
            ->icon('trash')
            ->attribute('data-id', 'check-button')
            ->modal($modal);

        return GridToolbarItem::make()
            ->content($button);
    }
}
