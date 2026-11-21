<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\SearchController::actionIndex()
 *
 * @var View $this
 * @var array<string, string> $models
 * @var Pagination $pagination
 * @var string $query
 * @var list<SearchResult> $results
 */

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\Toolbars\SearchModelFilterDropdown;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\Toolbars\SearchResultHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SearchHeader;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\Pagers\LinkPager;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;
use yii\data\Pagination;

echo SearchHeader::make()
    ->query($query)
    ->pagination($pagination->getPage() + 1);

echo Container::make()
    ->content(
        Card::make()
            ->content(Div::make()
                ->class('grid')
                ->content(
                    $query !== ''
                        ? SearchResultHeader::make()
                            ->content(SearchModelFilterDropdown::make()
                                ->items($models))
                        : null,
                    SearchResultList::make()
                        ->attribute('id', SearchController::LIST_ID)
                        ->addClass('search-results-striped')
                        ->query($query)
                        ->results($results)
                        ->emptyText($query !== ''
                            ? Yii::t('skeleton', 'SEARCH_EMPTY')
                            : Yii::t('skeleton', 'SEARCH_PLACEHOLDER')),
                )),
        LinkPager::make()
            ->pagination($pagination),
    );
