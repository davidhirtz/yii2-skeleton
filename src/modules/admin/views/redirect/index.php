<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */


use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use davidhirtz\yii2\skeleton\widgets\navs\Header;

$this->setTitle(Yii::t('skeleton', 'Redirects'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);

echo Header::make()
    ->title(A::make()
        ->text(Yii::t('skeleton', 'Redirects'))
        ->href(['index']));

echo GridContainer::make()
    ->grid(RedirectGridView::make()
        ->provider($provider));
