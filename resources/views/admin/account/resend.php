<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 */

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\AccountResendConfirmActiveForm;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

;

$this->title(Yii::t('skeleton', 'ACCOUNT_RESEND_TITLE'));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(AccountResendConfirmActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(
                StackItem::make()
                    ->label(Yii::t('skeleton', 'ACCOUNT_BACK_TO_LOGIN'))
                    ->icon('sign-in-alt')
                    ->url(Url::to(['login']))
                    ->visible(Application::current()->getUser()->getIsGuest())
            )
    );
