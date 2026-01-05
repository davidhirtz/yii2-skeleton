<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Closure;
use Hirtz\Skeleton\Assets\SortableAssetBundle;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Table;
use Hirtz\Skeleton\Html\Tbody;
use Hirtz\Skeleton\Html\Thead;
use Hirtz\Skeleton\Html\Tr;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Web\User;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Pagers\LinkPager;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridSearch;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
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
 * @template T of Model
 * @property T|null $model
 */
class GridView extends Widget
{
    use ContainerConfigurationTrait;
    use ModelWidgetTrait;
    use TagAttributesTrait;
    use TagIdTrait;

    public DataProviderInterface $provider;

    /**
     * @var array<Column|string>
     */
    public array $columns;

    /**
     * @var Stringable[]|Field[][]|string[][]|string[]|null
     */
    public ?array $footer = null;

    /**
     * @var Stringable[]|Field[][]|string[][]|string[]|null
     */
    public ?array $header = null;

    public array $headerAttributes = ['class' => 'grid-header'];
    public array $footerAttributes = ['class' => 'grid-footer'];
    public array $headerRowAttributes = [];
    public array $tableAttributes = ['class' => 'table table-striped table-hover'];
    public array $tableBodyAttributes = [];
    public array|Closure $rowAttributes;

    public bool $hasStickyFooter = true;
    public array $pagerOptions = [];
    public bool $showOnEmpty = true;

    public string $layout = '{header}{summary}{items}{pager}{footer}';
    public ?array $orderRoute = ['order'];

    protected GridSearch $search;
    protected User $webuser;

    public function __construct($config = [])
    {
        $this->search = GridSearch::make()->grid($this);
        $this->webuser = Yii::$app->getUser();

        parent::__construct($config);
    }

    public function provider(DataProviderInterface $data): static
    {
        $this->provider = $data;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->headerAttributes['hx-select'] ??= "#{$this->getId()}";
        $this->headerAttributes['hx-target'] ??= $this->headerAttributes['hx-select'];
        $this->headerAttributes['hx-boost'] ??= 'true';

        $this->headerRowAttributes['hx-select'] ??= "#{$this->getId()} table";
        $this->headerRowAttributes['hx-target'] ??= $this->headerRowAttributes['hx-select'];
        $this->headerRowAttributes['hx-boost'] ??= 'true';

        $this->rowAttributes ??= [];

        $this->model ??= $this->getModelFromProvider();
        $this->columns ??= $this->getDefaultColumns();

        $this->ensureColumns();

        parent::configure();
    }

    protected function ensureColumns(): void
    {
        $this->columns = array_values(array_filter($this->columns));

        foreach ($this->columns as $i => &$column) {
            if (is_string($column)) {
                $column = DataColumn::make()
                    ->property($column);
            }

            $column->grid($this);

            if (!$column->isVisible()) {
                unset($this->columns[$i]);
            }
        }
    }

    protected function renderContent(): string|Stringable
    {
        return $this->provider->getCount() || $this->showOnEmpty
            ? Div::make()
                ->attributes($this->attributes)
                ->addClass('grid')
                ->content($this->getContent())
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

    protected function getToolbars(array $rows, array $attributes = []): ?Div
    {
        $items = is_array(current($rows)) ? array_map($this->getToolbarItems(...), $rows) : $this->getToolbarItems($rows);

        return $items
            ? Div::make()
                ->attributes($attributes)
                ->content(...$items)
            : null;
    }

    protected function getToolbarItems(array $row): array
    {
        $items = [];

        foreach ($row as $item) {
            if (!$item instanceof GridToolbarItem) {
                $item = GridToolbarItem::make()
                    ->content($item);
            }

            if ($item->isVisible()) {
                $items[] = $item;
            }
        }

        return $items;
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
                ->content($this->getTable())
                ->class('table-wrap')
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
        $footer = $this->footer
            ? $this->getToolbars($this->footer, $this->footerAttributes)
            : null;

        return $footer?->addClass($this->hasStickyFooter ? 'sticky' : null);
    }

    /**
     * @return T|null
     */
    protected function getModelFromProvider(): ?Model
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

    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['update', 'id' => $model->getPrimaryKey(), ...$params];
    }

    protected function isSortable(): bool
    {
        return $this->provider->getSort() === false
            && $this->provider->getPagination() === false
            && $this->provider->getCount() > 1
            && !$this->search->getValue()
            && $this->orderRoute !== null;
    }
}
