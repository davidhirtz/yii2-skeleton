<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var User $user
 */

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AuthItemGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Edit Permissions'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);

echo UserSubmenu::widget(['user' => $user]);

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider)
        ->user($user));
