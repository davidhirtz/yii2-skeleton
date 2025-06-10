<?php

declare(strict_types=1);

/**
 * @see UserController::actionOwnership()
 * @var View $this
 * @var OwnershipForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\ErrorSummary;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\OwnershipActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;

$this->setTitle(Yii::t('skeleton', 'Transfer Ownership'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

$html = OwnershipActiveForm::widget([
    'model' => $form,
]);

echo UserSubmenu::widget();

echo ErrorSummary::make()
    ->models($form)
    ->title(Yii::t('skeleton', 'The site ownership could not be transferred'));

echo Card::make()
    ->danger()
    ->title($this->title)
    ->html($html);
