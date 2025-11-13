<?php

declare(strict_types=1);

/**
 * @see UserController::actionOwnership()
 * @var View $this
 * @var OwnershipForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\OwnershipActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;

$this->setTitle(Yii::t('skeleton', 'Transfer Ownership'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);


echo UserSubmenu::widget();

echo ErrorSummary::forModel($form)
    ->title(Yii::t('skeleton', 'The site ownership could not be transferred'));


echo Container::make()
    ->html(Card::make()
        ->danger()
        ->title($this->title)
        ->html(OwnershipActiveForm::widget([
            'model' => $form,
        ])));
