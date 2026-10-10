<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\ConsentController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\ConsentGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;
use yii\data\ActiveDataProvider;

echo Header::make()
    ->pagination($provider)
    ->title(Yii::t('skeleton', 'COMMON_CONSENTS'));

echo HintAlert::make()
    ->text(Yii::t('skeleton', 'CONSENT_INDEX_HINT'));

echo GridContainer::make()
    ->grid(ConsentGridView::make()
        ->provider($provider));
