<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionLogin()
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionResend()
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var LoginForm $form
 */

use Hirtz\Skeleton\Html\Noscript;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\LoginActiveForm;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

$this->title(Yii::t('skeleton', 'COMMON_LOGIN'));

echo Noscript::make()
    ->content(Container::make()
        ->content(Alert::make()
            ->danger()
            ->content(Yii::t('skeleton', 'ACCOUNT_NOSCRIPT'))));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(LoginActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'ACCOUNT_LOGIN_CREATE_ACCOUNT'))
                ->icon('user')
                ->url(['create'])
                ->visible(Yii::$app->getUser()->isSignupEnabled()))
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'ACCOUNT_LOGIN_RESEND_CONFIRMATION'))
                ->icon('envelope')
                ->url(['resend'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled() && !Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()))
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'ACCOUNT_LOGIN_RECOVER_PASSWORD'))
                ->icon('key')
                ->url(['recover'])
                ->visible(Yii::$app->getUser()->isPasswordResetEnabled()))
    );
