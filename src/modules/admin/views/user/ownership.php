<?php

declare(strict_types=1);

/**
 * @see UserController::actionOwnership()
 * @var View $this
 * @var OwnershipForm $form
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Models\Forms\OwnershipForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\OwnershipActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use Hirtz\Skeleton\Widgets\Panels\Card;

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
