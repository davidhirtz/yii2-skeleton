<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\RedirectGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\RedirectHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo RedirectHeader::make()
    ->provider($provider);

echo HintAlert::make()
    ->text(Yii::t('skeleton', 'REDIRECT_INDEX_HINT'));

echo GridContainer::make()
    ->grid(RedirectGridView::make()
        ->provider($provider));
