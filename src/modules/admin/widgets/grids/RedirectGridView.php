<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DeleteButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\ViewButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\CreateButton;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider $provider
 */
class RedirectGridView extends GridView
{
    use TypeGridViewTrait;

    public ?Redirect $redirect = null;
    public bool $showSelection = true;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'redirects');

        if ($this->redirect) {
            $this->setDataProviderFromRedirect();
            $this->setRedirectOptions();
        }

        $this->columns ??= [
//            $this->typeIconColumn(),
            $this->requestUriColumn(),
//            $this->urlColumn(),
//            $this->updatedAtColumn(),
//            $this->buttonsColumn(),
        ];

        //        if ($this->showSelection) {
        //            array_unshift($this->columns, [
        //                'class' => CheckboxColumn::class,
        //            ]);
        //        }

        parent::init();
    }

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->search->getToolbarItem(),
            ],
        ];
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateButton(),
                $this->showSelection ? $this->getSelectionButton() : null,
            ],
        ];
    }

    protected function setDataProviderFromRedirect(): void
    {
        $this->provider = Yii::createObject(RedirectActiveDataProvider::class);

        $this->provider->query
            ->andWhere(['url' => $this->redirect->getOldAttribute('url')])
            ->andWhere(['!=', 'id', $this->redirect->id]);
    }

    protected function setRedirectOptions(): void
    {
        $this->showSelection = false;
        $this->layout = '{items}';
    }

    protected function requestUriColumn()
    {
        //        return Yii::createObject(DataColumn::class, [
        //            'attribute' => 'request_uri',
        //            'grid' => $this,
        //            'content' => fn (Redirect $redirect) => A::make()
        //                ->html(Html::markKeywords($redirect->request_uri, $this->search->getKeywords()))
        //                ->href($this->getRoute($redirect)),
        //        ]);
        return new DataColumn(
            attribute: 'request_uri',
            content: fn (Redirect $redirect) => A::make()
                ->content(Html::markKeywords($redirect->request_uri, $this->search->getKeywords()))
                ->href($this->getRoute($redirect)),
            grid: $this,
        );
        return [
            'attribute' => 'request_uri',
            'content' => fn (Redirect $redirect) => A::make()
                ->content(Html::markKeywords($redirect->request_uri, $this->search->getKeywords()))
                ->href($this->getRoute($redirect))
        ];
    }

    protected function urlColumn(): array
    {
        return [
            'attribute' => 'url',
            'content' => fn (Redirect $redirect) => A::make()
                ->icon('external-link-alt')
                ->content(Html::markKeywords($redirect->url ?: '/', $this->search->getKeywords()))
                ->href($redirect->url)
                ->target('_blank')
        ];
    }

    protected function updatedAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'updated_at',
            'contentAttributes' => ['class' => 'text-nowrap'],
            'headerAttributes' => ['class' => 'text-nowrap'],
            'displayAtBreakpoint' => 'md',
        ];
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonColumn::class,
            'content' => $this->getRowButtons(...),
        ];
    }

    protected function getCreateButton(): ?Stringable
    {
        return new CreateButton(Yii::t('skeleton', 'New Redirect'), ['/admin/redirect/create']);
    }

    /**
     * @see RedirectController::actionDeleteAll()
     */
    protected function getSelectionButton(): ?Stringable
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
            ->addClass('d-none', 'd-block-checked')
            ->modal($modal);
    }

    /**
     * @return array<Stringable>
     * @see RedirectController::actionDelete()
     * @see RedirectController::actionUpdate()
     */
    protected function getRowButtons(Redirect $redirect): array
    {
        return [
            Yii::createObject(ViewButton::class, [$redirect]),
            Yii::createObject(DeleteButton::class, [$redirect]),
        ];
    }
}
