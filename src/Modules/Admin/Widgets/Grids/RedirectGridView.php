<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\TypeIconColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\SelectionTrait;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Hirtz\Skeleton\Widgets\Link;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider|null $provider
 */
class RedirectGridView extends GridView
{
    use SelectionTrait;

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

        $this->configureSelection();

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getTypeColumn(),
            $this->getRequestUriColumn(),
            $this->getUrlColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

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
                ->href($redirect->getAdminRoute() ?: null));
    }

    protected function getUrlColumn(): ?Column
    {
        return DataColumn::make()
            ->property('url')
            ->content(fn (Redirect $redirect) => Link::make()
                ->icon('external-link-alt')
                ->content($this->search->markKeywords($redirect->url ?: '/'))
                ->href($redirect->getBaseUrl() . $redirect->url)
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
     * @return list<Stringable>
     */
    protected function getButtonColumnContent(Redirect $redirect): array
    {
        return [
            ViewGridButton::make()->model($redirect),
        ];
    }

    protected function getDeleteSelectionLabel(): string
    {
        return Yii::t('skeleton', 'REDIRECT_DELETE_SELECTED');
    }

    /**
     * @see RedirectController::actionDeleteAll()
     */
    protected function getDeleteSelectionRoute(): array
    {
        return ['/admin/redirect/delete-all'];
    }
}
