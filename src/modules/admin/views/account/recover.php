<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var Hirtz\Skeleton\models\forms\PasswordRecoverForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\panels\Card;
use Hirtz\Skeleton\widgets\panels\Stack;
use Hirtz\Skeleton\widgets\panels\StackItem;
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
