<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AccountController::actionCreate()
 *
 * @var Hirtz\Skeleton\web\View $this
 * @var Hirtz\Skeleton\models\forms\SignupForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\html\Noscript;
use Hirtz\Skeleton\modules\admin\widgets\forms\SignupActiveForm;
use Hirtz\Skeleton\widgets\Alert;
use Hirtz\Skeleton\widgets\forms\ErrorSummary;
use Hirtz\Skeleton\widgets\panels\Card;
use Hirtz\Skeleton\widgets\panels\Stack;
use Hirtz\Skeleton\widgets\panels\StackItem;

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
