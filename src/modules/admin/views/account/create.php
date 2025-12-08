<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionCreate()
 *
 * @var Hirtz\Skeleton\Web\View $this
 * @var Hirtz\Skeleton\Models\Forms\SignupForm $form
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Html\Noscript;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\SignupActiveForm;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

$this->title(Yii::t('skeleton', 'Sign up'));

echo Container::make()
    ->content(ErrorSummary::make()
        ->models($form)
        ->title(Yii::t('skeleton', 'Your account could not be created')));

echo Noscript::make()
    ->content(Container::make()
        ->content(Alert::make()
            ->danger()
            ->content(Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'))));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(SignupActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'Sign up with Facebook'))
                ->url(['auth', 'authclient' => 'facebook'])
                ->icon('brand:facebook')
                ->visible($form->isFacebookSignupEnabled()))
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'Back to login'))
                ->url(['login'])
                ->icon('sign-in-alt'))
    );
