<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionCreate()
 *
 * @var Hirtz\Skeleton\Web\View $this
 * @var Hirtz\Skeleton\Models\Forms\SignupForm $form
 */

use Hirtz\Skeleton\Html\Noscript;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\SignupActiveForm;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

$this->title(Yii::t('skeleton', 'ACCOUNT_CREATE_TITLE'));

echo Container::make()
    ->content(ErrorSummary::make()
        ->models($form)
        ->title(Yii::t('skeleton', 'ACCOUNT_CREATE_ERROR_SUMMARY')));

echo Noscript::make()
    ->content(Container::make()
        ->content(Alert::make()
            ->danger()
            ->content(Yii::t('skeleton', 'ACCOUNT_WARNING_NOSCRIPT'))));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(SignupActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'ACCOUNT_BACK_TO_LOGIN'))
                ->url(['login'])
                ->icon('sign-in-alt'))
    );
