<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\GrantController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $grants
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\GrantGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'GRANT_CONNECTED_APPS'));

echo AccountHeader::make()
    ->model($user);

echo AccountSubmenu::make();

echo GridContainer::make()
    ->title(Yii::t('skeleton', 'GRANT_CONNECTED_APPS'))
    ->grid(GrantGridView::make()
        ->provider($grants));
