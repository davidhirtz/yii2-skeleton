<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\grids;

use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\Button;
use Hirtz\Skeleton\models\Redirect;
use Hirtz\Skeleton\modules\admin\controllers\RedirectController;
use Hirtz\Skeleton\modules\admin\data\RedirectActiveDataProvider;
use Hirtz\Skeleton\widgets\grids\columns\ButtonColumn;
use Hirtz\Skeleton\widgets\grids\columns\buttons\DeleteGridButton;
use Hirtz\Skeleton\widgets\grids\columns\buttons\ViewGridButton;
use Hirtz\Skeleton\widgets\grids\columns\CheckboxColumn;
use Hirtz\Skeleton\widgets\grids\columns\DataColumn;
use Hirtz\Skeleton\widgets\grids\columns\RelativeTimeColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Skeleton\widgets\grids\toolbars\CreateButton;
use Hirtz\Skeleton\widgets\grids\traits\TypeGridViewTrait;
use Hirtz\Skeleton\widgets\Modal;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider|null $provider
 */
class RedirectGridView extends GridView
{
    use TypeGridViewTrait;

    public bool $showSelection = true;
    protected ?Redirect $redirect = null;

    public function redirect(Redirect $redirect): static
    {
        $this->redirect = $redirect;
        return $this;
    }

    #[Override]
    public function configure(): void
    {
        $this->attributes['id'] ??= 'redirects';

        if ($this->redirect) {
            $this->setDataProviderFromRedirect();
            $this->setRedirectOptions();
        }

        $this->header ??= [
            $this->getTypeDropdown(),
            $this->search->getToolbarItem(),
        ];

        $this->columns ??= [
            $this->getCheckboxColumn(),
            $this->getTypeIconColumn(),
            $this->getRequestUriColumn(),
            $this->getUrlColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        $this->footer ??= [
            $this->getCreateButton(),
            $this->showSelection ? $this->getSelectionButton() : null,
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

    protected function getCheckboxColumn(): ?CheckboxColumn
    {
        return $this->showSelection
            ? CheckboxColumn::make()
            : null;
    }

    protected function getRequestUriColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('request_uri')
            ->content(fn (Redirect $redirect): Stringable => A::make()
                ->content(Html::markKeywords($redirect->request_uri, $this->search->getKeywords()))
                ->href($this->getRoute($redirect)));
    }

    protected function getUrlColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('url')
            ->content(fn (Redirect $redirect): Stringable => A::make()
                ->icon('external-link-alt')
                ->content(Html::markKeywords($redirect->url ?: '/', $this->search->getKeywords()))
                ->href($redirect->url)
                ->target('_blank'));
    }

    protected function getUpdatedAtColumn(): RelativeTimeColumn
    {
        return RelativeTimeColumn::make()
            ->property('updated_at')
            ->hiddenForSmallDevices();
    }

    protected function getButtonColumn(): ButtonColumn
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

    protected function getCreateButton(): Stringable
    {
        return CreateButton::make()
            ->text(Yii::t('skeleton', 'New Redirect'))
            ->href(['/admin/redirect/create']);
    }

    /**
     * @see RedirectController::actionDeleteAll()
     */
    protected function getSelectionButton(): Stringable
    {
        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Delete selected'))
            ->text(Yii::t('skeleton', 'Are you sure you want to delete all selected items?'))
            ->footer(Button::make()
                ->danger()
                ->text(Yii::t('skeleton', 'Delete selected'))
                ->icon('trash')
                ->post(['/admin/redirect/delete-all'])
                ->attribute('hx-include', '[data-check]:checked'));

        return Button::make()
            ->danger()
            ->text(Yii::t('skeleton', 'Delete selected'))
            ->icon('trash')
            ->attribute('data-id', 'check-button')
            ->addClass('hidden block-has-checked')
            ->modal($modal);
    }
}
