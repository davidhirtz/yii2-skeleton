<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use Closure;
use davidhirtz\yii2\skeleton\assets\SortableAssetBundle;
use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Table;
use davidhirtz\yii2\skeleton\html\Tbody;
use davidhirtz\yii2\skeleton\html\Thead;
use davidhirtz\yii2\skeleton\html\Tr;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\web\User;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\pagers\LinkPager;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\GridToolbarItem;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;
use yii\helpers\Url;

/**
 * @template T of ActiveRecord
 */
class GridView extends Widget
{
    use ContainerConfigurationTrait;
    use TagIdTrait;

    public DataProviderInterface $provider;

    /**
     * @var Column[]
     */
    public array $columns;

    public ?array $footer = null;
    public ?array $header = null;


    public array $attributes = ['class' => 'grid-view'];
    public array $headerAttributes = ['class' => 'grid-view-header'];
    public array $footerAttributes = ['class' => 'grid-view-footer'];
    public array $headerRowAttributes;
    public array $tableAttributes = ['class' => 'table table-striped table-hover'];
    public array $tableBodyAttributes;
    public array|Closure $rowAttributes;

    public array $pagerOptions = [];
    public bool $showOnEmpty = true;

    public string $layout = '{header}{summary}{items}{pager}{footer}';
    public ?array $orderRoute = ['order'];

    protected GridSearch $search;
    protected User $webuser;

    public function __construct()
    {
        $this->search ??= GridSearch::make()
            ->grid($this);

        $this->webuser ??= Yii::$app->getUser();

        parent::__construct();
    }

    public function init(): void
    {
        $this->initColumns();

        $this->attributes['hx-select'] ??= "#{$this->getId()}";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-select-oob'] ??= '#flashes';

        $this->headerRowAttributes['hx-boost'] ??= 'true';
        $this->tableBodyAttributes ??= [];
        $this->rowAttributes ??= [];
    }

    protected function initColumns(): void
    {
        $this->columns ??= $this->getDefaultColumns();

        foreach ($this->columns as $i => $column) {
            $column->grid($this);

            if (!$column->isVisible()) {
                unset($this->columns[$i]);
            }
        }
    }

    public function provider(DataProviderInterface $data): static
    {
        $this->provider = $data;
        return $this;
    }

    protected function renderContent(): string|Stringable
    {
        return $this->provider->getCount() || $this->showOnEmpty
            ? Div::make()
                ->attributes($this->attributes)
                ->html($this->getContent())
            : '';
    }

    protected function getContent(): string
    {
        return strtr($this->layout, [
            '{header}' => $this->getHeader(),
            '{summary}' => $this->getSummary(),
            '{items}' => $this->getItems(),
            '{pager}' => $this->getPager(),
            '{footer}' => $this->getFooter(),
        ]);
    }

    protected function getHeader(): ?Stringable
    {
        return $this->header ? $this->getToolbars($this->header, $this->headerAttributes) : null;
    }

    protected function getToolbars(array $rows, array $attributes = []): ?Stringable
    {
        $result = is_array(current($rows)) ? array_map($this->getToolbar(...), $rows) : $this->getToolbar($rows);
        return $result ? Html::div($result, $attributes) : null;
    }

    protected function getToolbar(array $row): ?Stringable
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

    protected function getSummary(): ?Stringable
    {
        return Yii::createObject(GridSummary::class, [
            $this->provider->getCount(),
            $this->provider->getTotalCount(),
            $this->provider->getPagination(),
            $this->search,
        ]);
    }

    protected function getItems(): ?Stringable
    {
        return $this->provider->getCount()
            ? Div::make()
                ->html($this->getTable())
                ->class('table-responsive')
            : null;
    }

    protected function getTable(): Table
    {
        return Table::make()
            ->attributes($this->tableAttributes)
            ->header($this->getTableHeader())
            ->body($this->getTableBody());
    }

    protected function getDefaultColumns(): array
    {
        $models = $this->provider->getModels();
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

    protected function getTableHeader(): Thead
    {
        $tr = Tr::make()->attributes($this->headerRowAttributes);

        foreach ($this->columns as $column) {
            $tr->addCells($column->renderHeader());
        }

        return Thead::make()->rows($tr);
    }

    protected function getTableBody(): Tbody
    {
        if ($this->isSortable()) {
            $this->tableBodyAttributes['data-sort-url'] ??= Url::to($this->orderRoute);
            Yii::$app->getView()->registerAssetBundle(SortableAssetBundle::class);
        }

        $tbody = Tbody::make()
            ->attributes($this->tableBodyAttributes);

        $models = array_values($this->provider->getModels());
        $keys = $this->provider->getKeys();

        foreach ($models as $index => $model) {
            $tbody->addRows($this->getTableRow($model, $keys[$index], $index));
        }

        return $tbody;
    }

    protected function getTableRow(mixed $model, int|string $key, int $index): Tr
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
            $tr->addCells($column->renderBody($model, $key, $index));
        }

        return $tr;
    }

    protected function getPager(): string
    {
        $pagination = $this->provider->getPagination();

        if ($pagination === false || $this->provider->getCount() <= 0) {
            return '';
        }

        $class = ArrayHelper::remove($this->pagerOptions, 'class', LinkPager::class);

        return $class::widget([
            'pagination' => $pagination,
            'view' => Yii::$app->getView(),
        ]);
    }

    protected function getFooter(): ?Stringable
    {
        return $this->footer ? $this->getToolbars($this->footer, $this->footerAttributes) : null;
    }

    public function getModel(): ?Model
    {
        if ($this->provider instanceof ActiveDataProvider) {
            /** @var class-string<ActiveRecord>|null $modelClass */
            $modelClass = $this->provider->query->modelClass ?? null;
        }

        $modelClass ??= $this->provider instanceof ArrayDataProvider
            ? $this->provider->modelClass
            : null;

        if ($modelClass) {
            return $modelClass::instance();
        }

        $models = $this->provider->getModels();
        $model = reset($models);

        return $model instanceof Model ? $model : null;
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
        return $this->provider->getSort() === false
            && $this->provider->getPagination() === false
            && !$this->search->getValue()
            && $this->orderRoute !== null;
    }
}
