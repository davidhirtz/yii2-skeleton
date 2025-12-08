<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\models\forms\PasswordResetForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\panels\Card;

$this->title($form->user->password_hash
    ? Yii::t('skeleton', 'Set New Password')
    : Yii::t('skeleton', 'Create Password'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(PasswordResetActiveForm::make()
        ->model($form)));
