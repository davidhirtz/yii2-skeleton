<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use Closure;
use davidhirtz\yii2\skeleton\assets\SortableAssetBundle;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Table;
use davidhirtz\yii2\skeleton\html\Tbody;
use davidhirtz\yii2\skeleton\html\Thead;
use davidhirtz\yii2\skeleton\html\Tr;
use davidhirtz\yii2\skeleton\widgets\pagers\LinkPager;
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
    public Formatter $formatter;

    private ?ActiveRecord $_model = null;

    #[Override]
    public function init(): void
    {
        $this->attributes['id'] ??= $this->getId();
        $this->attributes['hx-select'] ??= '#' . $this->getId();
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-select-oob'] ??= '#flashes';

        $this->headerRowAttributes['hx-boost'] ??= 'true';

        $this->formatter ??= Yii::$app->getFormatter();

        $this->search ??= Yii::createObject(GridSearch::class, [$this]);

        if ($this->isSortable()) {
            $this->tableAttributes['data-sort-url'] ??= Url::to($this->orderRoute);
            $this->getView()->registerAssetBundle(SortableAssetBundle::class);
        }

        $this->columns ??= $this->getDefaultColumns();
        $this->initColumns();

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
            ? Div::make()
                ->html($this->renderContent())
                ->attributes($this->attributes)
                ->render()
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

    protected function renderItems(): string
    {
        return $this->dataProvider->getCount()
            ? Div::make()
                ->html($this->renderTable())
                ->class('table-responsive')
                ->render()
            : '';
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

    protected function renderSummary(): string
    {
        $summary = Yii::createObject(GridSummary::class, [
            $this->dataProvider->getCount(),
            $this->dataProvider->getTotalCount(),
            $this->dataProvider->getPagination(),
            $this->search,
        ]);

        return $summary->render();
    }

    protected function initHeader(): void
    {
    }

    protected function renderHeader(): string
    {
        $this->initHeader();

        $header = $this->header ? $this->renderRows($this->header) : '';

        if ($header) {
            $options = $this->header['options'] ?? [];
            Html::addCssClass($options, 'grid-view-header');
            $header = Html::tag('div', $header, $options);
        }

        return $header;
    }

    protected function initFooter(): void
    {
    }

    protected function renderFooter(): string
    {
        $this->initFooter();

        $footer = $this->footer ? $this->renderRows($this->footer) : '';

        if ($footer) {
            $options = $this->footer['options'] ?? [];
            Html::addCssClass($options, 'grid-view-footer');
            $footer = Html::tag('div', $footer, $options);
        }

        return $footer;
    }

    protected function renderRows(array $rows): string
    {
        $result = [];

        foreach ($rows as $row) {
            $items = [];

            foreach ($row as $item) {
                if (is_string($item) || $item instanceof Stringable) {
                    $content = (string)$item;

                    if ($content) {
                        $items[] = Html::tag('div', $content);
                    }
                } elseif (($item['visible'] ?? true) && ($item['content'] ?? null)) {
                    $items[] = Html::tag($item['tag'] ?? 'div', $item['content'], $item['options'] ?? []);
                }
            }

            if ($items) {
                $options = $row['options'] ?? [];
                Html::addCssClass($options, 'row');

                $result[] = Html::tag('div', implode('', $items), $options);
            }
        }

        return implode('', $result);
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

    /**
     * @param T $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['update', 'id' => $model->getPrimaryKey(), ...$params];
    }

    /**
     * @return T|null
     */
    protected function getModel(): ?ActiveRecord
    {
        if ($this->_model === null && $this->dataProvider instanceof ActiveDataProvider) {
            $model = $this->dataProvider->query->modelClass ?? null;
            $this->_model = $model ? Yii::createObject($model) : null;
        }

        return $this->_model;
    }

    /**
     * @param T $model
     * @noinspection PhpUnused
     */
    protected function setModel(ActiveRecord $model): void
    {
        $this->_model = $model;
    }

    /**
     * @return T[]
     */
    protected function getModels(): array
    {
        return $this->dataProvider->getModels();
    }

    protected function isSortable(): bool
    {
        return $this->dataProvider->getSort() === false
            && $this->dataProvider->getPagination() === false
            && !$this->search->value
            && $this->orderRoute !== null;
    }
}
