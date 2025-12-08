<?php

declare(strict_types=1);

/**
 * @see UserController::actionOwnership()
 * @var View $this
 * @var OwnershipForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\models\forms\OwnershipForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\OwnershipActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\ErrorSummary;
use Hirtz\Skeleton\widgets\panels\Card;

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
