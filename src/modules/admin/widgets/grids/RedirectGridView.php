<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Redirect>
 * @property RedirectActiveDataProvider $dataProvider
 */
class RedirectGridView extends GridView
{
    use TypeGridViewTrait;

    
    public bool $showSelection = true;

    /**
     * @var array the url route for selection update
     * @see RedirectController::actionDeleteAll()
     */
    public array $selectionRoute = ['delete-all'];

    /**
     * @var Redirect|null the model used to display additional redirects
     */
    public ?Redirect $redirect = null;

    public function init(): void
    {
        if ($this->redirect) {
            $this->setDataProviderFromRedirect();
            $this->setRedirectOptions();
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
                [
                    'content' => $this->getSearchInput(),
                    'options' => [
                        'class' => 'col-12 col-md-6',
                    ],
                ],
                'options' => [
                    'class' => 'justify-content-end',
                ],
            ],
        ];

        parent::initHeader();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => $this->getCreateButton() . ($this->showSelection ? $this->getDeleteAllButton() : ''),
                    'options' => ['class' => 'col'],
                ],
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

    public function run(): void
    {
        if (!$this->redirect || $this->dataProvider->getCount() > 0) {
            parent::run();
        }
    }

    public function requestUriColumn(): array
    {
        return [
            'attribute' => 'request_uri',
            'content' => fn (Redirect $redirect) => Html::a(Html::markKeywords($redirect->request_uri, $this->getSearchKeywords()), $this->getRoute($redirect))
        ];
    }

    public function urlColumn(): array
    {
        return [
            'attribute' => 'url',
            'content' => function (Redirect $redirect) {
                $text = Html::iconText('external-link-alt', Html::markKeywords($redirect->url ?: '/', $this->getSearchKeywords()), ['class' => 'text-nowrap']);
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
            'displayAtBreakpoint' => 'md',
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => fn (Redirect $redirect): string => Html::buttons($this->getRowButtons($redirect))
        ];
    }

    protected function getCreateButton(): string
    {
        return Html::a(Html::iconText('plus', Yii::t('skeleton', 'New Redirect')), ['/admin/redirect/create'], ['class' => 'btn btn-primary']);
    }

    protected function getDeleteAllButton(): string
    {
        return Html::button(Yii::t('skeleton', 'Delete selected'), [
            'id' => 'btn-selection',
            'class' => 'btn btn-danger',
            'style' => 'display:none',
            'data-method' => 'post',
            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete all selected items?'),
            'data-form' => $this->getSelectionFormId(),
        ]);
    }

    protected function getDeleteRoute(ActiveRecordInterface $model, array $params = []): array
    {
        return parent::getDeleteRoute($model, [...$params, 'previous' => $this->redirect->id ?? null]);
    }

    protected function getRowButtons(Redirect $redirect): array|string
    {
        return [
            $this->getUpdateButton($redirect),
            $this->getDeleteButton($redirect),
        ];
    }

    public function getModel(): Redirect
    {
        return Redirect::instance();
    }
}
