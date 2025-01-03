<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\assets\SortableAssetBundle;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Btn;
use davidhirtz\yii2\skeleton\html\Modal;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\widgets\pagers\LinkPager;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecordInterface;
use yii\grid\CheckboxColumn;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/**
 * @template T of ActiveRecord
 * @property ActiveDataProvider|ArrayDataProvider|null $dataProvider
 */
class GridView extends \yii\grid\GridView
{
    /**
     * @var array|null containing the header rows
     */
    public ?array $header = null;

    /**
     * @var array|null containing the footer rows
     */
    public ?array $footer = null;

    public GridSearch $search;

    /**
     * @var array|null the url route for sortable widget
     */
    public ?array $orderRoute = ['order'];

    public $tableOptions = [
        'class' => 'table table-striped table-hover',
    ];

    public $emptyText = false;
    public $layout = '{header}{summary}{items}{pager}{footer}';

    public $pager = [
        'class' => LinkPager::class,
        'firstPageLabel' => true,
        'lastPageLabel' => true,
    ];

    /**
     * @var bool whether the items should receive a {@see yii\grid\CheckboxColumn} and moved inside a wrapping form
     */
    public bool $showSelection = false;

    /**
     * @var array the url route for selection update
     */
    public array $selectionRoute = ['update-all'];


    public ?string $selectionButtonLabel = null;

    /**
     * @var array containing the selection form HTML options
     */
    public array $selectionColumn = [
        'class' => CheckboxColumn::class,
    ];

    private ?ActiveRecord $_model = null;
    private ?string $_formName = null;

    public function init(): void
    {
        $this->options['hx-swap-oob'] ??= 'true';

        if ($this->showSelection) {
            array_unshift($this->columns, $this->selectionColumn);
            $this->getView()->registerJs('Skeleton.initSelection("#' . $this->getSelectionFormId() . '")');
        }

        if (!$this->rowOptions) {
            $this->rowOptions = fn ($record) => $record instanceof ActiveRecord ? ['id' => $this->getRowId($record)] : [];
        }

        $this->selectionButtonLabel ??= Yii::t('skeleton', 'Update Selected');
        $this->tableOptions['id'] ??= $this->getTableId();

        $this->search ??= Yii::$container->get(GridSearch::class, [$this]);

        parent::init();
    }

    protected function initColumns(): void
    {
        foreach ($this->columns as &$column) {
            if (is_string($column)) {
                $methodName = lcfirst(Inflector::camelize($column)) . 'Column';

                if (method_exists($this, $methodName)) {
                    $column = call_user_func([$this, $methodName]);
                }
            }
        }

        $this->columns = array_filter($this->columns);

        parent::initColumns();
    }

    public function renderItems(): string
    {
        if ($this->dataProvider->getCount() || $this->emptyText) {
            $table = $this->showSelection ? $this->renderSelectionForm(parent::renderItems()) : parent::renderItems();
            return Html::tag('div', $table, ['class' => 'table-responsive']);
        }

        return '';
    }

    protected function renderSelectionForm(string $items): string
    {
        return Html::beginForm($this->selectionRoute, 'post', ['id' => $this->getSelectionFormId()])
            . $items
            . Html::endForm();
    }

    public function renderTableBody(): string
    {
        $tableBody = parent::renderTableBody();

        if ($this->isSortedByPosition()) {
            $attributes = [
                'class' => 'sortable',
                'data-sort-url' => Url::to($this->orderRoute),
            ];

            $tableBody = preg_replace('/^<tbody/', '<tbody ' . Html::renderTagAttributes($attributes), $tableBody);
            SortableAssetBundle::registerModule("#$this->id tbody");
        }

        return $tableBody;
    }

    public function renderSummary(): string
    {
        $summary = $this->summary;
        $totalCount = $this->dataProvider->getTotalCount();
        $count = $this->dataProvider->getCount();
        $params = $this->getSummaryParams();

        if (!$summary) {
            if ($this->search->value) {
                $summary = match ($count) {
                    1 => Yii::t('skeleton', 'Displaying the only result matching "{search}".', $params),
                    0 => Yii::t('skeleton', 'Sorry, no results found matching matching "{search}".', $params),
                    $totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} results matching "{search}".', $params),
                    default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} results matching "{search}".', $params),
                };
            } else {
                $summary = match ($count) {
                    1 => Yii::t('skeleton', 'Displaying the only record.', $params),
                    0 => Yii::t('skeleton', 'Sorry, no records found.', $params),
                    $totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} records.', $params),
                    default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} records.', $params),
                };
            }
        } else {
            $summary = Yii::$app->getI18n()->format($summary, $params, Yii::$app->language);
        }

        $summaryOptions = $this->summaryOptions;
        $summaryOptions['route'] ??= $this->search->url;

        Html::addCssClass($summaryOptions, $totalCount ? 'alert-info' : 'alert-warning');

        return Html::alert($summary, $summaryOptions);
    }

    protected function getSummaryParams(): array
    {
        $params = [
            'search' => $this->search->value,
            'totalCount' => $this->dataProvider->getTotalCount(),
        ];

        $pagination = $this->dataProvider->getPagination();

        if ($pagination !== false) {
            $params['page'] = $pagination->getPage() + 1;
            $params['pageCount'] = $pagination->pageCount;
            $params['begin'] = $pagination->getPage() * $pagination->pageSize + 1;
            $params['end'] = $params['begin'] + $this->dataProvider->getCount() - 1;
            $params['begin'] = min($params['begin'], $params['end']);
        }

        return $params;
    }

    protected function initHeader(): void
    {
    }

    public function renderHeader(): string
    {
        $this->initHeader();

        $options = ArrayHelper::remove($this->header, 'options', []);
        Html::addCssClass($options, 'grid-view-header');

        return $this->header ? Html::tag('div', $this->renderRows($this->header), $options) : '';
    }

    protected function initFooter(): void
    {
    }

    public function renderFooter(): string
    {
        $this->initFooter();

        $options = ArrayHelper::remove($this->footer, 'options', []);
        Html::addCssClass($options, 'grid-view-footer');

        return $this->footer
            ? Html::tag('div', $this->renderRows($this->footer), $options)
            : '';
    }

    public function renderRows(array $rows): string
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
                Html::addCssClass($options, 'row');
                $result[] = Html::tag('div', implode('', $items), $options);
            }
        }

        return implode('', $result);
    }

    public function renderSection($name): string|false
    {
        return match ($name) {
            '{header}' => $this->renderHeader(),
            '{footer}' => $this->renderFooter(),
            default => parent::renderSection($name),
        };
    }

    /**
     * @noinspection PhpUnused
     */
    public function getSelectionButton(array $options = []): string
    {
        if ($items = $this->getSelectionButtonItems()) {
            return ButtonDropdown::widget([
                'label' => Html::iconText('wrench', $this->selectionButtonLabel),
                'buttonOptions' => ['class' => 'btn-submit'],
                'options' => [
                    'id' => 'btn-selection',
                    'style' => 'display:none',
                ],
                'direction' => ButtonDropdown::DIRECTION_UP,
                ...$options,
                'items' => $items,
            ]);
        }

        return '';
    }

    protected function getSelectionButtonItems(): array
    {
        return [];
    }

    public function getFormName(): ?string
    {
        if ($this->_formName === null) {
            if ($model = $this->getModel()) {
                $this->_formName = Inflector::camel2id(StringHelper::basename($model->formName()));
            }
        }

        return $this->_formName;
    }

    /**
     * @noinspection PhpUnused
     */
    public function setFormName(string $formName): void
    {
        $this->_formName = $formName;
    }

    protected function getSelectionFormId(): string
    {
        return $this->getFormName() . '-items';
    }

    public function getTableId(): string
    {
        return $this->getFormName() . '-table';
    }

    public function getRowId(ActiveRecordInterface $model): string
    {
        return $this->getFormName() . '-' . implode('-', (array)$model->getPrimaryKey());
    }

    /**
     * @noinspection PhpUnused
     */
    protected function getSortableButton(array $options = []): string
    {
        $icon = ArrayHelper::remove($options, 'icon', 'arrows-alt');

        return Html::tag('span', (string)Icon::tag($icon), [
            'class' => 'btn btn-secondary sortable-handle',
            ...$options,
        ]);
    }

    /**
     * @param T $model
     */
    protected function getUpdateButton(ActiveRecordInterface $model, array $options = []): string
    {
        $icon = ArrayHelper::remove($options, 'icon', 'wrench');

        return Btn::primary()
            ->icon($icon)
            ->href($this->getRoute($model))
            ->addClass('d-none d-md-inline-block')
            ->addAttributes($options)
            ->render();
    }

    /**
     * @param T $model
     */
    protected function getDeleteButton(ActiveRecordInterface $model, array $options = []): string
    {
        $icon = ArrayHelper::remove($options, 'icon', 'trash');
        $message = ArrayHelper::remove($options, 'message', Yii::t('yii', 'Are you sure you want to delete this item?'));
        $id = 'delete-' . $this->getRowId($model);

        $modal = Modal::tag()
            ->id($id)
            ->title($message)
            ->footer(Btn::danger()
                ->text(Yii::t('yii', 'Delete'))
                ->delete($this->getDeleteRoute($model))
                ->target('#' . $this->getRowId($model)));

        $btn = Btn::danger()
            ->icon($icon)
            ->modal("#$id")
            ->addAttributes($options)
            ->render();

        return $modal . $btn;

        //        return Html::a((string)Icon::tag($icon), $this->getDeleteRoute($model), [
        //            'class' => 'btn btn-danger',
        //            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
        //            'data-ajax' => 'remove',
        //            'data-target' => '#' . $this->getRowId($model),
        //            ...$options,
        //        ]);
    }

    /**
     * @param T $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['update', 'id' => $model->getPrimaryKey(), ...$params];
    }

    /**
     * @param T $model
     */
    protected function getDeleteRoute(ActiveRecordInterface $model, array $params = []): array
    {
        return ['delete', 'id' => $model->getPrimaryKey(), ...$params];
    }

    /**
     * @return T|null
     */
    public function getModel(): ?ActiveRecord
    {
        if ($this->_model === null) {
            if ($this->dataProvider instanceof ActiveDataProvider) {
                $model = $this->dataProvider->query->modelClass ?? null;
                $this->_model = $model ? Yii::createObject($model) : null;
            }
        }

        return $this->_model;
    }

    /**
     * @param T $model
     * @noinspection PhpUnused
     */
    public function setModel(ActiveRecord $model): void
    {
        $this->_model = $model;
    }

    /**
     * @return T[]
     */
    public function getModels(): array
    {
        return $this->dataProvider->getModels();
    }

    public function isSortedByPosition(): bool
    {
        return $this->dataProvider->getSort() === false
            && $this->dataProvider->getPagination() === false
            && $this->orderRoute !== null;
    }
}
