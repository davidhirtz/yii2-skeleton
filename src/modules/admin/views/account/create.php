<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionCreate()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\SignupForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Noscript;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\SignupActiveForm;
use davidhirtz\yii2\skeleton\widgets\Alert;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;
use davidhirtz\yii2\skeleton\widgets\panels\Stack;
use davidhirtz\yii2\skeleton\widgets\panels\StackItem;

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
