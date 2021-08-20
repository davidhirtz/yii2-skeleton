<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

/**
 * Class RedirectGridView
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base
 *
 * @property RedirectActiveDataProvider $dataProvider
 */
class RedirectGridView extends GridView
{
    use TypeGridViewTrait;

    /**
     * @var bool
     */
    public $showSelection = true;

    /**
     * @var array the url route for selection update
     */
    public $selectionRoute = ['delete-all'];

    /**
     * @var Redirect the model used to display additional redirects
     */
    public $redirect;

    /**
     * @inheritDoc
     */
    public function init()
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

        if ($this->header === null) {
            $this->header = [
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
        }

        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => $this->getCreateButton() . ($this->showSelection ? $this->getDeleteAllButton() : ''),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }

        parent::init();
    }

    /**
     * Sets data provider.
     */
    protected function setDataProviderFromRedirect()
    {
        $this->dataProvider = new RedirectActiveDataProvider();

        $this->dataProvider->query->andWhere(['url' => $this->redirect->getOldAttribute('url')])
            ->andWhere(['!=', 'id', $this->redirect->id]);
    }

    /**
     * Sets options if `redirect` model is set.
     */
    protected function setRedirectOptions()
    {
        $this->showSelection = false;
        $this->layout = '{items}';
    }

    /**
     * Runs the widget only if data is present.
     */
    public function run()
    {
        if (!$this->redirect || $this->dataProvider->getCount() > 0) {
            parent::run();
        }
    }

    /**
     * @return array
     */
    public function requestUriColumn()
    {
        return [
            'attribute' => 'request_uri',
            'content' => function (Redirect $redirect) {
                return Html::a(Html::markKeywords($redirect->request_uri, $this->getSearchKeywords()), $this->getRoute($redirect));
            }
        ];
    }

    /**
     * @return array
     */
    public function urlColumn()
    {
        return [
            'attribute' => 'url',
            'content' => function (Redirect $redirect) {
                $text = Html::iconText('external-link-alt', Html::markKeywords($redirect->url, $this->getSearchKeywords()), ['class' => 'text-nowrap']);
                return Html::a($text, $redirect->getBaseUrl() . $redirect->url, [
                    'target' => '_blank',
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            'content' => function (Redirect $redirect) {
                return Timeago::tag($redirect->updated_at);
            }
        ];
    }

    /**
     * @return array
     */
    public function buttonsColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (Redirect $redirect) {
                return Html::buttons($this->getRowButtons($redirect));
            }
        ];
    }

    /**
     * @return string
     */
    protected function getCreateButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('skeleton', 'New Redirect')), ['/admin/redirect/create'], ['class' => 'btn btn-primary']);
    }

    /**
     * @return string
     */
    protected function getDeleteAllButton()
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

    /**
     * @param Redirect $model
     * @param array $params
     * @return array|false
     */
    protected function getDeleteRoute($model, $params = [])
    {
        return parent::getDeleteRoute($model, array_merge($params, ['previous' => $this->redirect->id ?? null]));
    }

    /**
     * @param Redirect $redirect
     * @return array|string
     */
    protected function getRowButtons($redirect)
    {
        return [
            $this->getUpdateButton($redirect),
            $this->getDeleteButton($redirect),
        ];
    }

    /**
     * @return Redirect
     */
    public function getModel()
    {
        return Redirect::instance();
    }
}