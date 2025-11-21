<?php

declare(strict_types=1);

/**
 * @see UserController::actionOwnership()
 * @var View $this
 * @var OwnershipForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\OwnershipForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\OwnershipActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;

$this->title(Yii::t('skeleton', 'Transfer Ownership'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);


echo UserSubmenu::make();

echo Container::make()
    ->content(ErrorSummary::make()->models($form)
        ->title(Yii::t('skeleton', 'The site ownership could not be transferred')));


echo Container::make()
    ->content(Card::make()
        ->danger()
        ->title($this->title)
        ->content(OwnershipActiveForm::make()
            ->model($form)));
