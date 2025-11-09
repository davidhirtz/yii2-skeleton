<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\buttons\DeleteButton;
use davidhirtz\yii2\skeleton\widgets\buttons\ViewButton;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider $dataProvider
 */
class RedirectGridView extends GridView
{
    use TypeGridViewTrait;

    public bool $showSelection = true;

    /**
     * @var Redirect|null the model used to display additional redirects
     */
    public ?Redirect $redirect = null;

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'redirects');

        if ($this->redirect) {
            $this->setDataProviderFromRedirect();
            $this->setRedirectOptions();
            $this->showOnEmpty = true;
        }

        if (!$this->columns) {
            $this->columns = [
                $this->typeIconColumn(),
                $this->requestUriColumn(),
                $this->urlColumn(),
                $this->updatedAtColumn(),
                $this->buttonsColumn(),
            ];
        }

        parent::init();
    }

    protected function initHeader(): void
    {
        $this->header ??= [
            [
                $this->search->getColumn(),
            ],
        ];

        parent::initHeader();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                $this->getCreateButton(),
                $this->showSelection ? $this->getSelectionButton() : '',
            ],
        ];

        parent::initFooter();
    }

    protected function setDataProviderFromRedirect(): void
    {
        $this->dataProvider = Yii::createObject(RedirectActiveDataProvider::class);

        $this->dataProvider->query->andWhere(['url' => $this->redirect->getOldAttribute('url')])
            ->andWhere(['!=', 'id', $this->redirect->id]);
    }

    protected function setRedirectOptions(): void
    {
        $this->showSelection = false;
        $this->layout = '{items}';
    }

    public function requestUriColumn(): array
    {
        return [
            'attribute' => 'request_uri',
            'content' => fn (Redirect $redirect) => Html::a(Html::markKeywords($redirect->request_uri, $this->search->getKeywords()), $this->getRoute($redirect))
        ];
    }

    public function urlColumn(): array
    {
        return [
            'attribute' => 'url',
            'content' => function (Redirect $redirect) {
                $text = Html::iconText('external-link-alt', Html::markKeywords($redirect->url ?: '/', $this->search->getKeywords()), ['class' => 'text-nowrap']);
                return Html::a($text, $redirect->getBaseUrl() . $redirect->url, [
                    'target' => '_blank',
                ]);
            }
        ];
    }

    public function updatedAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'updated_at',
            'contentOptions' => ['class' => 'text-nowrap'],
            'headerOptions' => ['class' => 'text-nowrap'],
            'displayAtBreakpoint' => 'md',
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => fn (Redirect $redirect) => $this->getRowButtons($redirect),
        ];
    }

    protected function getCreateButton(): string
    {
        return Button::primary(Yii::t('skeleton', 'New Redirect'))
            ->icon('plus')
            ->get(['/admin/redirect/create'])
            ->render();
    }

    /**
     * @see RedirectController::actionDeleteAll()
     */
    #[Override]
    protected function getSelectionButton(): Stringable|string
    {
        $modal = Modal::make()
            ->title(Yii::t('skeleton', 'Delete selected'))
            ->text(Yii::t('skeleton', 'Are you sure you want to delete all selected items?'))
            ->footer(Button::danger(Yii::t('skeleton', 'Delete selected'))
                ->icon('trash')
                ->post(['/admin/redirect/delete-all'])
                ->attribute('hx-include', '[data-id="check"]:checked'));

        return Button::danger(Yii::t('skeleton', 'Delete selected'))
            ->icon('trash')
            ->attribute('data-id', 'check-button')
            ->attribute('style', 'display:none')
            ->modal($modal);
    }

    /**
     * @see RedirectController::actionUpdate()
     * @see RedirectController::actionDelete()
     */
    protected function getRowButtons(Redirect $redirect): array|string
    {
        return [
            ViewButton::widget(['model' => $redirect]),
            DeleteButton::widget([
                'model' => $redirect,
                'url' => [
                    'delete',
                    'id' => $redirect->id,
                    'previous' => $this->redirect->id ?? null,
                ],
            ]),
        ];
    }

    #[Override]
    public function getModel(): Redirect
    {
        return Redirect::instance();
    }
}
