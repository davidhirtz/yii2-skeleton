<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionUpdate()
 *
 * @var View $this
 * @var Redirect $redirect
 * @var RedirectActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use davidhirtz\yii2\skeleton\widgets\navs\Header;

$this->setTitle(Yii::t('skeleton', 'Update Redirect'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);

echo Header::make()
    ->title(Yii::t('skeleton', 'Redirects'))
    ->url(['index']);

echo FormContainer::make()
    ->form(RedirectActiveForm::make()
        ->model($redirect));

echo GridContainer::make()
    ->title(Yii::t('skeleton', 'Additional Redirects'))
    ->grid(RedirectGridView::make()
        ->redirect($redirect));

echo FormContainer::make()
    ->danger()
    ->title(Yii::t('skeleton', 'Delete Redirect'))
    ->form(DeleteActiveForm::widget([
        'model' => $redirect,
    ]));
