<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var Hirtz\Skeleton\Models\Forms\PasswordRecoverForm $form
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\PasswordRecoverActiveForm;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Panels\Card;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;
use yii\helpers\Url;

$this->title(Yii::t('skeleton', 'Recover Password'));

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
                    ->label(Yii::t('skeleton', 'Back to login'))
                    ->icon('sign-in-alt')
                    ->url(Url::to(['login']))
                    ->visible(Yii::$app->getUser()->getIsGuest())
            )
    );
