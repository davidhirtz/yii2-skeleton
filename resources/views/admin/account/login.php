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
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

$this->title(Yii::t('skeleton', 'USER_LOGIN_TYPE_LOGIN_NAME'));

echo Noscript::make()
    ->content(Container::make()
        ->content(Alert::make()
            ->danger()
            ->content(Yii::t('skeleton', 'SIGNUP_NOSCRIPT_WARNING'))));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(LoginActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'SIGNUP_BUTTON'))
                ->icon('user')
                ->url(['create'])
                ->visible(Application::current()->getUser()->isSignupEnabled()))
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'ACCOUNT_RESEND_BUTTON'))
                ->icon('envelope')
                ->url(['resend'])
                ->visible(!Application::current()->getUser()->isUnconfirmedEmailLoginEnabled()))
            ->addItem(StackItem::make()
                ->label(Yii::t('skeleton', 'PASSWORD_RECOVER_BUTTON'))
                ->icon('key')
                ->url(['recover'])
                ->visible(Application::current()->getUser()->isPasswordResetEnabled()))
    );
