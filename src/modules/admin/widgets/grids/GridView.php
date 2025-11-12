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
use davidhirtz\yii2\skeleton\html\Td;
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
use yii\helpers\StringHelper;
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

    public array $tableOptions = [
        'class' => 'table table-striped table-hover',
    ];

    public array $options = ['class' => 'grid-view'];
    public array $headerRowOptions = [];
    public array|Closure $rowOptions = [];
    public array $pagerOptions = [];

    public string|null|false $emptyText;
    public array $emptyTextOptions = ['class' => 'empty'];

    public string $layout = '{header}{summary}{items}{pager}{footer}';
    public ?array $orderRoute = ['order'];

    public Formatter $formatter;

    public string $emptyCell = '&nbsp;';
    public null $filterModel = null;

    private ?ActiveRecord $_model = null;
    private ?string $_formName = null;

    #[Override]
    public function init(): void
    {
        $this->options['id'] ??= $this->getId();
        $this->options['hx-select'] ??= '#' . $this->getId();
        $this->options['hx-target'] ??= $this->options['hx-select'];
        $this->options['hx-select-oob'] ??= '#flashes';

        $this->headerRowOptions['hx-boost'] ??= 'true';

        if (!$this->rowOptions) {
            $this->rowOptions = fn ($record) => $record instanceof ActiveRecord
                ? ['id' => $this->getRowId($record)]
                : [];
        }

        $this->formatter ??= Yii::$app->getFormatter();

        $this->search ??= Yii::createObject(GridSearch::class, [$this]);

        $this->columns ??= $this->getDefaultColumns();
        $this->initColumns();

        parent::init();
    }

    protected function initColumns(): void
    {
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $method = lcfirst(Inflector::camelize($column)) . 'Column';
                $column = method_exists($this, $method) ? call_user_func([$this, $method]) : ['attribute' => $column];
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
        $content = $this->dataProvider->getCount() > 0
            ? $this->renderSections()
            : $this->renderEmpty();

        return $content ? Html::tag('div', $content, $this->options) : '';
    }

    protected function renderSections(): string
    {
        return preg_replace_callback('/{\\w+}/', function ($matches) {
            $content = $this->renderSection($matches[0]);
            return $content === false ? $matches[0] : $content;
        }, $this->layout);
    }

    protected function renderItems(): string
    {
        return $this->dataProvider->getCount() || $this->emptyText ?
            Div::make()
                ->html($this->renderTable())
                ->class('table-responsive')
                ->render()
            : '';
    }

    protected function renderTable(): Stringable
    {
        return Table::make()
            ->attributes($this->tableOptions)
            ->header($this->renderTableHeader())
            ->body($this->renderTableBody());
    }

    protected function renderTableHeader(): Thead
    {
        $tr = Tr::make()->attributes($this->headerRowOptions);

        foreach ($this->columns as $column) {
            $tr->addCells(Td::make()->html($column instanceof Column ? $column->renderHeaderCell() : ''));
        }

        return Thead::make()->rows($tr);
    }

    protected function renderTableBody(): Tbody
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        $tbody = Tbody::make();

        if ($this->isSortable()) {
            $tbody->addClass('sortable')
                ->attribute('data-sort-url', Url::to($this->orderRoute));

            SortableAssetBundle::registerModule("#{$tbody->getId()}");
        }

        foreach ($models as $index => $model) {
            $tbody->addRows($this->renderTableRow($model, $keys[$index], $index));
        }

        return $tbody;
    }

    public function renderTableRow(mixed $model, int $key, int $index): Tr
    {
        $tr = Tr::make()
            ->attributes($this->rowOptions instanceof Closure
                ? call_user_func($this->rowOptions, $model, $key, $index, $this)
                : $this->rowOptions);

        foreach ($this->columns as $column) {
            $tr->addCells(Td::make()
                ->html($column instanceof Column
                    ? $column->renderDataCell($model, $key, $index)
                    : ''));
        }

        return $tr;
    }

    public function renderSummary(): string
    {
        $summary = Yii::createObject(GridSummary::class, [
            $this->dataProvider->getCount(),
            $this->dataProvider->getTotalCount(),
            $this->dataProvider->getPagination(),
            $this->search,
        ]);

        return $summary->render();
    }

    protected function renderEmpty(): string
    {
        if ($this->emptyText === false) {
            return '';
        }

        return Div::make()
            ->html($this->emptyText ?? Yii::t('yii', 'No results found.'))
            ->attributes($this->emptyTextOptions)
            ->render();
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

    protected function renderSection($name): string|false
    {
        return match ($name) {
            '{header}' => $this->renderHeader(),
            '{footer}' => $this->renderFooter(),
            '{summary}' => $this->renderSummary(),
            '{items}' => $this->renderItems(),
            '{pager}' => $this->renderPager(),
            default => $name,
        };
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

    public function getRowId(ActiveRecordInterface $model): string
    {
        return $this->getFormName() . '-' . implode('-', (array)$model->getPrimaryKey());
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
    public function getModel(): ?ActiveRecord
    {
        if ($this->_model === null) {
            if ($this->dataProvider instanceof ActiveDataProvider) {
                $models = $this->dataProvider->getModels();
                $this->_model = reset($models);
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

    protected function isSortable(): bool
    {
        return $this->dataProvider->getSort() === false
            && $this->dataProvider->getPagination() === false
            && !$this->search->value
            && $this->orderRoute !== null;
    }
}
