<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Logins'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);

echo UserSubmenu::widget(['user' => $user]);

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider)
        ->user($user));
