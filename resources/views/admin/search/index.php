<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\SearchController::actionIndex()
 *
 * @var View $this
 * @var array<string, string> $models
 * @var SearchDataProvider $provider
 * @var string $query
 */

use Hirtz\Skeleton\Modules\Admin\Data\SearchDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\SearchResultGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SearchHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Grids\GridSearch;

echo SearchHeader::make()
    ->query($query)
    ->pagination($provider->getPagination() ? $provider->getPagination()->getPage() + 1 : null);

echo GridContainer::make()
    ->grid(SearchResultGridView::make()
        ->provider($provider)
        ->search(GridSearch::make()->value($query))
        ->models($models));
