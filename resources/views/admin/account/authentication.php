<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AccountController::actionLogin()
 *
 * @var Hirtz\Skeleton\Web\View $this
 * @var Hirtz\Skeleton\Models\Forms\LoginForm $form
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\TwoFactorAuthenticationLoginActiveForm;
use Hirtz\Skeleton\Widgets\Panels\Card;

$this->title(Yii::t('skeleton', 'Two-Factor Authentication'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(TwoFactorAuthenticationLoginActiveForm::make()
            ->model($form)));
