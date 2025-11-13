<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use Closure;
use davidhirtz\yii2\skeleton\assets\SortableAssetBundle;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Table;
use davidhirtz\yii2\skeleton\html\Tbody;
use davidhirtz\yii2\skeleton\html\Thead;
use davidhirtz\yii2\skeleton\html\Tr;
use davidhirtz\yii2\skeleton\widgets\grids\pagers\LinkPager;
use Override;
use Stringable;
use Yii;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\grid\Column;
use yii\grid\DataColumn;
use yii\helpers\Inflector;
use yii\helpers\Url;
use yii\i18n\Formatter;

/**
 * @template T of ActiveRecord
 */
class GridView extends Widget
{
    public DataProviderInterface $dataProvider;

    /**
     * @var array<int, string|Column|array>
     */
    public array $columns;

    public ?array $footer = null;
    public ?array $header = null;

    public GridSearch $search;

    public array $attributes = ['class' => 'grid-view'];
    public array $headerAttributes = ['class' => 'grid-view-header'];
    public array $footerAttributes = ['class' => 'grid-view-footer'];
    public array $headerRowAttributes = [];
    public array $tableAttributes = ['class' => 'table table-striped table-hover'];
    private array $tableBodyAttributes = [];
    public array|Closure $rowAttributes = [];

    public array $pagerOptions = [];
    public bool $showOnEmpty = true;

    public string $layout = '{header}{summary}{items}{pager}{footer}';
    public ?array $orderRoute = ['order'];

    /**
     * @noinspection PhpUnused
     */
    public string $emptyCell = '&nbsp;';
    public null $filterModel = null;

    public function __construct(public Formatter $formatter, $config = [])
    {
        parent::__construct($config);
    }

    #[Override]
    public function init(): void
    {
        $this->attributes['id'] ??= $this->getId();
        $this->attributes['hx-select'] ??= '#' . $this->getId();
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-select-oob'] ??= '#flashes';

        $this->headerRowAttributes['hx-boost'] ??= 'true';

        $this->search ??= Yii::createObject(GridSearch::class, [$this]);

        if ($this->isSortable()) {
            $this->tableBodyAttributes['data-sort-url'] ??= Url::to($this->orderRoute);
            $this->getView()->registerAssetBundle(SortableAssetBundle::class);
        }

        $this->columns ??= $this->getDefaultColumns();

        $this->initHeader();
        $this->initColumns();
        $this->initFooter();


        parent::init();
    }

    protected function initColumns(): void
    {
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = ['attribute' => $column];
            }

            if (is_array($column)) {
                $column['class'] ??= DataColumn::class;
                $column['grid'] = $this;

                $column = Yii::createObject($column);
            }

            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }

            $this->columns[$i] = $column;
        }
    }

    protected function getDefaultColumns(): array
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        $columns = [];

        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || $value instanceof Stringable) {
                    $columns[] = (string)$name;
                }
            }
        }

        return $columns;
    }

    #[Override]
    public function run(): string
    {
        return $this->dataProvider->getCount() || $this->showOnEmpty
            ? Html::div($this->renderContent(), $this->attributes)->render()
            : '';
    }

    protected function renderContent(): string
    {
        return strtr($this->layout, [
            '{header}' => $this->renderHeader(),
            '{footer}' => $this->renderFooter(),
            '{summary}' => $this->renderSummary(),
            '{items}' => $this->renderItems(),
            '{pager}' => $this->renderPager(),
        ]);
    }

    protected function renderItems(): ?Stringable
    {
        return $this->dataProvider->getCount()
            ? Html::div($this->renderTable())->class('table-responsive')
            : null;
    }

    protected function renderTable(): Table
    {
        return Table::make()
            ->attributes($this->tableAttributes)
            ->header($this->renderTableHeader())
            ->body($this->renderTableBody());
    }

    protected function renderTableHeader(): Thead
    {
        $tr = Tr::make()->attributes($this->headerRowAttributes);

        foreach ($this->columns as $column) {
            $tr->addCells($column instanceof Column ? $column->renderHeaderCell() : '');
        }

        return Thead::make()->rows($tr);
    }

    protected function renderTableBody(): Tbody
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        $tbody = Tbody::make()
            ->attributes($this->tableBodyAttributes);

        foreach ($models as $index => $model) {
            $tbody->addRows($this->renderTableRow($model, $keys[$index], $index));
        }

        return $tbody;
    }

    protected function renderTableRow(mixed $model, int|string $key, int $index): Tr
    {
        $attributes = $this->rowAttributes instanceof Closure
            ? call_user_func($this->rowAttributes, $model, $key, $index, $this)
            : $this->rowAttributes;

        if ($model instanceof ActiveRecord) {
            $attributes['id'] ??= implode('-', [
                Inflector::camel2id($model->formName()),
                ...$model->getPrimaryKey(true),
            ]);
        }

        $tr = Tr::make()
            ->attributes($attributes);

        foreach ($this->columns as $column) {
            $tr->addCells($column instanceof Column
                ? $column->renderDataCell($model, $key, $index)
                : '');
        }

        return $tr;
    }

    protected function renderSummary(): ?Stringable
    {
        return Yii::createObject(GridSummary::class, [
            $this->dataProvider->getCount(),
            $this->dataProvider->getTotalCount(),
            $this->dataProvider->getPagination(),
            $this->search,
        ]);
    }

    protected function initHeader(): void
    {
    }

    protected function renderHeader(): ?Stringable
    {
        return $this->header ? $this->renderToolbars($this->header, $this->headerAttributes) : null;
    }

    protected function initFooter(): void
    {
    }

    protected function renderFooter(): ?Stringable
    {
        return $this->footer ? $this->renderToolbars($this->footer, $this->footerAttributes) : null;
    }

    protected function renderToolbars(array $rows, array $attributes = []): ?Stringable
    {
        $result = array_map(fn ($row) => $this->renderToolbar($row), $rows);
        return $result ? Html::div($result, $attributes) : null;
    }

    protected function renderToolbar(array $row): ?Stringable
    {
        $items = [];

        foreach ($row as $item) {
            if ($item instanceof Stringable && !$item instanceof GridToolbarItem) {
                $item = (string)$item;
            }

            if (is_string($item) || is_array($item)) {
                $item = Yii::createObject(GridToolbarItem::class, (array)$item);
            }

            if ($item instanceof GridToolbarItem && $item->visible) {
                $items[] = $item;
            }
        }

        return $items ? Html::div($items)->addClass('row') : null;
    }

    protected function renderPager(): string
    {
        $pagination = $this->dataProvider->getPagination();

        if ($pagination === false || $this->dataProvider->getCount() <= 0) {
            return '';
        }

        $class = ArrayHelper::remove($this->pagerOptions, 'class', LinkPager::class);

        return $class::widget([
            'pagination' => $pagination,
            'view' => $this->getView(),
        ]);
    }

    public function getModel(): ?ActiveRecord
    {
        if ($this->dataProvider instanceof ActiveDataProvider) {
            /** @var class-string<ActiveRecord>|null $model */
            $model = $this->dataProvider->query->modelClass ?? null;
            return $model ? $model::instance() : null;
        }

        return null;
    }

    /**
     * @param T $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['update', 'id' => $model->getPrimaryKey(), ...$params];
    }

    protected function isSortable(): bool
    {
        return $this->dataProvider->getSort() === false
            && $this->dataProvider->getPagination() === false
            && !$this->search->value
            && $this->orderRoute !== null;
    }
}
