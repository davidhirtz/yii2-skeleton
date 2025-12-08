<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AccountController::actionLogin()
 *
 * @var Hirtz\Skeleton\web\View $this
 * @var Hirtz\Skeleton\models\forms\LoginForm $form
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\modules\admin\widgets\forms\TwoFactorAuthenticationLoginActiveForm;
use Hirtz\Skeleton\widgets\panels\Card;

$this->title(Yii::t('skeleton', 'Two-Factor Authentication'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(TwoFactorAuthenticationLoginActiveForm::make()
            ->model($form)));
