<?php

declare(strict_types=1);

/**
 * @see AccountController::actionSecurity()
 *
 * @var View $this
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\TwoFactorAuthenticatorActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\AccountSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

echo Header::make()
    ->title($user->getUsername());

echo AccountSubmenu::make();

echo FormContainer::make()
    ->form(TwoFactorAuthenticatorActiveForm::make()
        ->model($user));
