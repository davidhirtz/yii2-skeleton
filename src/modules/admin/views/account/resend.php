<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\models\forms\AccountResendConfirmForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\AccountResendConfirmActiveForm;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\panels\Card;
use Hirtz\Skeleton\widgets\panels\Stack;
use Hirtz\Skeleton\widgets\panels\StackItem;
use yii\helpers\Url;

$this->title(Yii::t('skeleton', 'Resend Account Confirmation'));

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
                    ->label(Yii::t('skeleton', 'Back to login'))
                    ->icon('sign-in-alt')
                    ->url(Url::to(['login']))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    );
