<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Closure;
use Hirtz\Skeleton\Assets\SortableAssetBundle;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\ArrayHelper;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Table;
use Hirtz\Skeleton\Html\Tbody;
use Hirtz\Skeleton\Html\Thead;
use Hirtz\Skeleton\Html\Tr;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Html\Traits\TagIdTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Pagers\LinkPager;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridFooter;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridHeader;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridSearchForm;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\helpers\Inflector;

class GridView extends Widget
{
    use ContainerConfigurationTrait;

    use ModelTrait;
    use ProviderTrait;

    use TagAttributesTrait;
    use TagIdTrait;

    /**
     * @var array<Column|string>
     */
    protected array $columns;

    /**
     * @var list<string|Stringable>|GridFooter|null
     */
    protected array|GridFooter|null $footer = null;
    protected array $footerAttributes = ['class' => 'grid-footer sticky'];

    /**
     * @var list<string|Stringable>|GridHeader|null
     */
    protected ?array $header = null;
    protected array $headerAttributes = ['class' => 'grid-header'];

    protected array $tableAttributes = ['class' => 'table table-striped table-hover'];
    protected array $tableHeaderAttributes = [];
    protected array $tableBodyAttributes = [];

    protected array|Closure|null $rowAttributes = null;

    protected array $pagerOptions = [];
    protected bool $showOnEmpty = true;

    protected string $layout = '{header}{summary}{items}{pager}{footer}';
    protected ?array $orderRoute = ['order'];

    public GridSearch $search;

    public function __construct(array $config = [])
    {
        $this->search ??= GridSearch::make();
        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->headerAttributes['hx-select'] ??= "#{$this->getId()}";
        $this->headerAttributes['hx-target'] ??= $this->headerAttributes['hx-select'];
        $this->headerAttributes['hx-boost'] ??= 'true';

        $this->tableHeaderAttributes['hx-select'] ??= "#{$this->getId()} table";
        $this->tableHeaderAttributes['hx-target'] ??= $this->tableHeaderAttributes['hx-select'];
        $this->tableHeaderAttributes['hx-boost'] ??= 'true';

        $this->columns ??= $this->getDefaultColumns();

        $this->ensureColumns();

        parent::configure();
    }

    protected function ensureColumns(): void
    {
        $this->columns = array_values(array_filter($this->columns));

        foreach ($this->columns as $i => &$column) {
            if (is_string($column)) {
                $column = DataColumn::make()->property($column);
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

    protected function getHeader(): ?GridHeader
    {
        return is_array($this->header)
            ? GridHeader::make()->attributes($this->headerAttributes)->content(...$this->header)
            : $this->header;
    }

    protected function getSearchInput(): ?Stringable
    {
        return GridSearchForm::make()->grid($this);
    }

    protected function getSummary(): ?Stringable
    {
        return GridSummary::make()->grid($this);
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
        $tr = Tr::make()->attributes($this->tableHeaderAttributes);

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
            ? ($this->rowAttributes)($model, $key, $index, $this)
            : $this->rowAttributes ?? [];

        if ($model instanceof ActiveRecord) {
            $attributes['id'] ??= implode('-', [
                Inflector::camel2id($model->formName()),
                ...$model->getPrimaryKey(true),
            ]);
        }

        $tr = Tr::make()->attributes($attributes);

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

    protected function getFooter(): ?GridFooter
    {
        return is_array($this->footer)
            ? GridFooter::make()->attributes($this->footerAttributes)->content(...$this->footer)
            : $this->footer;
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
