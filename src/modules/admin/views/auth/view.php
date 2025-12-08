<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AuthController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var User $user
 */

use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\widgets\grids\AuthItemGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Edit Permissions'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);

echo UserSubmenu::make()
    ->user($user);

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider)
        ->user($user));
