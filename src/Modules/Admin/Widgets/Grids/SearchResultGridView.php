<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\Toolbars\SearchModelFilterDropdown;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Icon;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;
use Override;
use Stringable;
use Yii;

/**
 * @extends GridView<array{result: SearchResult}>
 */
class SearchResultGridView extends GridView
{
    /**
     * @var array<string, string>
     */
    protected array $models = [];
    protected bool $showBadges = true;
    protected string $layout = '{header}{items}{pager}{footer}';
    private ?SearchResultList $resultList = null;

    /**
     * The filter's items: the labels of the classes the user sees, by their `model` key.
     *
     * @param array<string, string> $models
     */
    public function models(array $models): static
    {
        $this->models = $models;
        return $this;
    }

    public function showBadges(bool $showBadges = true): static
    {
        $this->showBadges = $showBadges;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'search-results-grid';

        $this->header ??= [
            SearchModelFilterDropdown::make()
                ->items($this->models),
        ];

        $this->columns ??= [
            $this->getIconColumn(),
            $this->getResultColumn(),
            $this->getUpdatedColumn(),
        ];

        parent::configure();
    }

    /**
     * The page header already names the query, so only an empty search says something.
     */
    #[Override]
    protected function getItems(): ?Stringable
    {
        return parent::getItems() ?? Alert::make()
            ->warning()
            ->text($this->search->getValue() !== ''
                ? Yii::t('skeleton', 'SEARCH_EMPTY')
                : Yii::t('skeleton', 'SEARCH_PLACEHOLDER'));
    }

    protected function getIconColumn(): LinkColumn
    {
        return LinkColumn::make()
            ->property('result.icon')
            ->title(false)
            ->format('raw')
            ->bodyAttributes(['class' => 'text-center'])
            ->value(fn (array $row): string => $row['result']->icon
                ? (string)Icon::make()->name($row['result']->icon)->tooltip($row['result']->type)
                : '')
            ->url($this->getRoute(...));
    }

    protected function getResultColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('result.title')
            ->title(Yii::t('skeleton', 'SEARCH_RESULT_LABEL'))
            ->content(fn (array $row): string => (string)$this->getResultList()->renderResult($row['result']));
    }

    protected function getUpdatedColumn(): LinkColumn
    {
        return RelativeTimeColumn::make()
            ->property('result.updated')
            ->title(Yii::t('skeleton', 'MODEL_UPDATED_AT_LABEL'))
            ->url($this->getRoute(...));
    }

    /**
     * @param array{result: SearchResult} $row
     * @return array<array-key, mixed>|string|null
     */
    protected function getRoute(array $row): array|string|null
    {
        return $row['result']->route ?: $row['result']->url;
    }

    protected function getResultList(): SearchResultList
    {
        return $this->resultList ??= SearchResultList::make()
            ->query($this->search->getValue())
            ->showBadges($this->showBadges)
            ->showIcons(false);
    }
}
