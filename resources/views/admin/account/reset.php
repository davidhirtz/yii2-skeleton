<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\PasswordResetActiveForm;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Panels\Card;

$this->title($form->user->password_hash
    ? Yii::t('skeleton', 'ACCOUNT_RESET_TITLE')
    : Yii::t('skeleton', 'ACCOUNT_RESET_CREATE_TITLE'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(PasswordResetActiveForm::make()
        ->model($form)));
