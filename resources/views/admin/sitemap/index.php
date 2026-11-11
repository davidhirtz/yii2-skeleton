<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\SitemapController::actionIndex()
 *
 * @var View $this
 * @var SitemapUrlArrayDataProvider $provider
 * @var string $key
 * @var int $offset
 */

use Hirtz\Skeleton\Modules\Admin\Data\SitemapUrlArrayDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\SitemapUrlGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SitemapHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\SitemapSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('skeleton', 'SITEMAP_TITLE'));

echo SitemapHeader::make()
    ->provider($provider)
    ->key($key);

echo SitemapSubmenu::make()
    ->current($key, $offset);

echo HintAlert::make()
    ->text(Yii::t('skeleton', 'SITEMAP_INDEX_HINT'));

echo GridContainer::make()
    ->grid(SitemapUrlGridView::make()
        ->provider($provider));
