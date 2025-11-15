<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;
use davidhirtz\yii2\skeleton\widgets\navs\Header;

$this->setTitle(Yii::t('skeleton', 'Create New Redirect'));
$this->setBreadcrumb(Yii::t('skeleton', 'Redirects'), ['index']);

echo Header::make()
    ->title(Yii::t('skeleton', 'Redirects'))
    ->url(['index']);

echo ErrorSummary::make()->models($redirect);

echo FormContainer::make()
    ->title($this->title)
    ->form(RedirectActiveForm::widget([
        'model' => $redirect,
    ]));
