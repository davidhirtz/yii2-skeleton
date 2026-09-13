<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var Hirtz\Skeleton\Models\Forms\PasswordRecoverForm $form
 */

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\PasswordRecoverActiveForm;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;

;

$this->title(Yii::t('skeleton', 'ACCOUNT_RECOVER_TITLE'));

echo Container::make()
    ->centered()
    ->content(
        Card::make()
            ->title($this->title)
            ->content(PasswordRecoverActiveForm::make()
                ->model($form)),
        Stack::make()
            ->addItem(
                StackItem::make()
                    ->label(Yii::t('skeleton', 'ACCOUNT_BACK_TO_LOGIN'))
                    ->icon('sign-in-alt')
                    ->url(Url::to(['login']))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    );
