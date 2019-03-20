<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\grid\SortableWidget;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/**
 * Class GridView.
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets
 */
class GridView extends \yii\grid\GridView
{
    /**
     * @var array|null
     */
    public $header;

    /**
     * @var array|null
     */
    public $footer;

    /**
     * @var string the search string, leave empty to set it via request param.
     * @see $searchParamName
     */
    public $search;

    /**
     * @var string the param name of the search query.
     */
    public $searchParamName = 'q';

    /**
     * @var array search form option.
     */
    public $searchFormOptions = [];

    /**
     * @var string the default route used for search.
     */
    public $searchUrl;

    /**
     * @var array
     */
    public $orderRoute = ['order'];

    /**
     * @var array
     */
    public $tableOptions = [
        'class' => 'table table-vertical table-striped table-hover',
    ];

    /**
     * @var string
     */
    public $emptyText = false;

    /**
     * @var \yii\data\ActiveDataProvider|\yii\data\ArrayDataProvider
     */
    public $dataProvider;

    /**
     * @var string
     */
    public $layout = '{header}{summary}{items}{pager}{footer}';

    /**
     * @var array
     */
    public $pager = [
        'class' => '\davidhirtz\yii2\skeleton\widgets\pagers\LinkPager',
        'firstPageLabel' => true,
        'lastPageLabel' => true,
    ];

    /**
     * @var ActiveRecord
     */
    private $_model;

    /**
     * @var string
     */
    private $_formName;

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach ($this->columns as &$column) {
            if (is_string($column)) {
                $methodName = lcfirst(Inflector::camelize($column)) . 'Column';
                if (method_exists($this, $methodName)) {
                    $column = call_user_func([$this, $methodName]);
                }
            }
        }

        if (!$this->rowOptions) {
            $this->rowOptions = function ($record) {
                return $record instanceof ActiveRecord ? ['id' => $this->getFormName() . '-' . implode('-', (array)$record->getPrimaryKey())] : [];
            };
        }

        ArrayHelper::setDefaultValue($this->tableOptions, 'id', $this->getTableId());

        parent::init();
    }

    /***********************************************************************
     * Render methods.
     ***********************************************************************/

    /**
     * @return string|null
     */
    public function renderItems()
    {
        if ($this->getIsSortedByPosition()) {
            SortableWidget::widget([
                'id' => $this->tableOptions['id'] . ' tbody',
                'ajaxUpdateRoute' => $this->orderRoute,
                'cloneHelperWidth' => true,
                'clientOptions' => [
                    'handle' => '.sortable-handle',
                    'axis' => 'y',
                ],
            ]);
        }

        return $this->dataProvider->getCount() || $this->emptyText ? parent::renderItems() : null;
    }

    /**
     * @return string
     */
    public function renderSummary()
    {
        $summary = $this->summary;
        $options = $this->summaryOptions;
        $totalCount = $this->dataProvider->getTotalCount();
        $count = $this->dataProvider->getCount();

        $params = [
            'search' => $this->search,
            'totalCount' => $totalCount,
        ];

        if (($pagination = $this->dataProvider->getPagination()) !== false) {
            $params['page'] = $pagination->getPage() + 1;
            $params['pageCount'] = $pagination->pageCount;
            $params['begin'] = $pagination->getPage() * $pagination->pageSize + 1;
            $params['end'] = $params['begin'] + $count - 1;
            $params['begin'] = min($params['begin'], $params['end']);
        }

        if ($summary === null) {
            if ($this->search) {
                switch ($count) {
                    case 1:

                        $summary = Yii::t('skeleton', 'Displaying the only result matching "{search}".', $params);
                        break;

                    case 0:

                        $summary = Yii::t('skeleton', 'Sorry, no results found matching matching "{search}".', $params);
                        break;

                    case $pagination === false:
                    case $totalCount:

                        $summary = Yii::t('skeleton', 'Displaying all {totalCount, number} results matching "{search}".', $params);
                        break;

                    default:
                        $summary = Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} results matching "{search}".', $params);
                        break;

                }
            } else {
                switch ($count) {
                    case 1:

                        $summary = Yii::t('skeleton', 'Displaying the only record.', $params);
                        break;

                    case 0:

                        $summary = Yii::t('skeleton', 'Sorry, no records found.', $params);
                        break;

                    case $pagination === false:
                    case $totalCount:

                        $summary = Yii::t('skeleton', 'Displaying all {totalCount, number} records.', $params);
                        break;

                    default:
                        $summary = Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} records.', $params);
                        break;
                }
            }
        } else {
            $summary = Yii::$app->getI18n()->format($summary, $params, Yii::$app->language);
        }

        $tag = ArrayHelper::remove($options, 'tag', 'div');
        Html::addCssClass($options, $totalCount ? ['alert', 'alert-info'] : ['alert', 'alert-warning']);

        if ($this->search) {
            Html::addCssClass($options, 'alert-dismissible');
            $summary .= Html::a(Html::tag('span', '&times;', ['aria-hidden' => true]), $this->searchUrl, [
                'class' => 'close',
                'aria-label' => Yii::t('skeleton', 'Close')
            ]);
        }

        return Html::tag($tag, $summary, $options);
    }

    /**
     * @return string
     */
    public function renderHeader()
    {
        return $this->header ? Html::tag('div', is_array($this->header) ? $this->renderRows($this->header) : $this->header, ['class' => 'grid-view-header']) : null;
    }

    /**
     * @return string
     */
    public function renderFooter()
    {
        return $this->footer ? Html::tag('div', is_array($this->footer) ? $this->renderRows($this->footer) : $this->footer, ['class' => 'grid-view-footer']) : null;
    }

    /**
     * @param array $rows
     * @return string
     */
    public function renderRows($rows)
    {
        $result = [];

        foreach ($rows as $row) {
            $options = ArrayHelper::remove($row, 'options', []);
            $items = [];

            foreach ($row as $item) {
                if (ArrayHelper::getValue($item, 'visible', true) && $content = ArrayHelper::getValue($item, 'content')) {
                    $items[] = Html::tag(ArrayHelper::getValue($item, 'tag', 'div'), $content, ArrayHelper::getValue($item, 'options', []));
                }
            }

            if ($items) {
                Html::addCssClass($options, ['row']);
                $result[] = Html::tag('div', implode('', $items), $options);
            }
        }

        return implode('', $result);
    }

    /**
     * @inheritdoc
     */
    public function renderSection($name)
    {
        switch ($name) {
            case '{header}':
                return $this->renderHeader();

            case '{footer}':
                return $this->renderFooter();

            default:
                return parent::renderSection($name);
        }
    }

    /**
     * @return string
     */
    public function getSearchInput()
    {
        if ($this->searchUrl === null) {
            $this->searchUrl = Url::current([$this->searchParamName => null]);
        }

        if ($this->search === null) {
            $this->search = trim(Yii::$app->getRequest()->get($this->searchParamName));
        }

        $options = [
            'class' => 'form-control',
            'prepend' => Html::submitButton(FAS::icon(ArrayHelper::remove($this->searchFormOptions, 'icon', 'search'), ['class' => 'fa-fw']), ['class' => 'btn-transparent']),
            'placeholder' => Yii::t('skeleton', 'Search ...'),
        ];

        return Html::beginForm($this->searchUrl, 'get') .
            Html::input('search', $this->searchParamName, $this->search, array_merge($options, $this->searchFormOptions)) .
            Html::endForm();
    }

    /***********************************************************************
     * Getters / setters.
     ***********************************************************************/

    /**
     * @throws \yii\base\InvalidConfigException
     * @return string
     */
    public function getFormName()
    {
        if ($this->_formName === null) {
            if ($model = $this->getModel()) {
                $this->_formName = Inflector::camel2id(StringHelper::basename($model->formName()));
            }
        }

        return $this->_formName;
    }

    /**
     * @param string $formName
     */
    public function setFormName($formName)
    {
        $this->_formName = $formName;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return string
     */
    public function getTableId()
    {
        return $this->getFormName() . '-table';
    }

    /**
     * @return array
     */
    public function getSearchKeywords()
    {
        return array_filter(explode(' ', $this->search));
    }

    /**
     * @return bool
     */
    public function getIsSortedByPosition()
    {
        return $this->dataProvider->getSort() === false && $this->dataProvider->getPagination() === false;
    }

    /**
     * @return ActiveRecord
     */
    public function getModel()
    {
        if (!$this->_model) {
            if ($this->dataProvider instanceof \yii\data\ActiveDataProvider) {
                /**  @var \davidhirtz\yii2\skeleton\db\ActiveQuery $query */
                $query = $this->dataProvider->query;
                $this->_model = new $query->modelClass;
            }
        }

        return $this->_model;
    }

    /**
     * @param ActiveRecord $model
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }

    /**
     * @return ActiveRecord[]
     */
    public function getModels()
    {
        return $this->dataProvider->getModels();
    }
}