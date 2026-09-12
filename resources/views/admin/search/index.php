<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\SearchController::actionIndex()
 *
 * @var View $this
 * @var Pagination $pagination
 * @var string $query
 * @var list<SearchResult> $results
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SearchController;
use Hirtz\Skeleton\Search\SearchResult;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\Pagers\LinkPager;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Search\SearchResultList;
use yii\data\Pagination;

echo Header::make()
    ->title($query !== ''
        ? Yii::t('skeleton', 'SEARCH_HEADER_RESULTS', ['query' => $query])
        : Yii::t('skeleton', 'SEARCH_HEADER_TITLE'))
    ->pagination($pagination->getPage() + 1);

echo Container::make()
    ->content(
        Card::make()
            ->content(SearchResultList::make()
                ->attribute('id', SearchController::LIST_ID)
                ->query($query)
                ->results($results)
                ->emptyText($query !== ''
                    ? Yii::t('skeleton', 'SEARCH_EMPTY')
                    : Yii::t('skeleton', 'SEARCH_PLACEHOLDER'))),
        LinkPager::widget(['pagination' => $pagination]),
    );
